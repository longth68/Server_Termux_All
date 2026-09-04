package nro.virtualplayer;

import nro.player.Player;
import nro.virtualplayer.core.VirtualGoals;
import nro.virtualplayer.core.VirtualMemory;
import nro.virtualplayer.core.VirtualNeeds;
import nro.virtualplayer.core.VirtualProfile;
import nro.virtualplayer.core.VirtualState;
import nro.inventory.InventoryService;
import models.Item.ItemService;
import Utils.Util;

/**
 * Virtual Player - Người chơi ảo sống trong thế giới game.
 * PHASE 2 - Virtual Player Core.
 * Mở rộng Player, mang theo profile, memory, needs, goals, brain.
 */
public class VirtualPlayer extends Player {

    public long vpId;                 // ID nhận diện trong hệ thống VP (dùng làm player.id)
    public VirtualProfile profile;
    public VirtualMemory memory;
    public VirtualNeeds needs;
    public VirtualGoals goals;
    public VirtualState state;
    private VirtualEquipment equipment;

    private VirtualBrain brain;

    // Ngoại hình lưu riêng (head kế thừa từ Player, body/leg lưu tại đây)
    public short body_;
    public short leg_;

    // Điều khiển hoạt động
    public boolean active = true;
    public long timeSpawned;
    public long timeOffline;          // thời điểm logout (để tính giờ login lại)
    public long lastTimeTick;
    public long tickInterval;         // chu kỳ tick riêng (phân tán workload)
    public boolean lodFar;            // LOD: không có player thật gần -> mô phỏng nhẹ

    // Hiện diện luân phiên: bot đang "ghé thăm" khu của một player thật (KHÔNG cố định)
    public long presenceHostId;       // id người chơi thật đang ghé thăm (0 = không ghé ai, sống độc lập)
    public long presenceEndTime;      // thời điểm hết lượt ghé thăm -> thả về đời sống độc lập
    public long lastEmote;            // mốc gửi emotion gần nhất (VirtualEmotion, cooldown 15s)
    public String adItemName;         // món đang rao bán (VirtualTrade)
    public long adPrice;              // giá rao bán
    public long lastTradeReply;       // mốc trả lời chat trade gần nhất (chống spam)
    public final java.util.Queue<VirtualTrade.PendingReply> pendingReplies =
            new java.util.concurrent.ConcurrentLinkedQueue<>(); // tin nhắn riêng chờ giao

    public VirtualPlayer(long id, String name, byte gender, short head, short body, short leg, short flag, byte cFlag) {
        // Player() constructor đã wire nPoint, inventory, playerSkill, ...
        this.id = id;
        this.vpId = id;
        this.name = name;
        this.gender = gender;
        this.head = head;
        this.body_ = body;
        this.leg_ = leg;
        this.isBot = true;
        this.isBot_New = true;
        this.isPlayer = false;

        // Khởi tạo inventory rỗng (100 bag + 20 body)
        this.inventory.gold = 2_000_000_000L;
        this.inventory.gem = 20000;
        this.inventory.ruby = 20000;
        for (int i = 0; i < 100; i++) {
            this.inventory.itemsBag.add(ItemService.gI().createItemNull());
        }
        for (int i = 0; i < 20; i++) {
            this.inventory.itemsBody.add(ItemService.gI().createItemNull());
        }

        this.nPoint.stamina = 1000;
        this.nPoint.maxStamina = 1000;

        // Core AI
        this.profile = new VirtualProfile(name);
        this.memory = new VirtualMemory();
        this.needs = new VirtualNeeds();
        this.goals = new VirtualGoals();
        this.state = VirtualState.SPAWN;
        this.equipment = new VirtualEquipment(this);
        this.brain = new VirtualBrain(this);

        this.timeSpawned = System.currentTimeMillis();
        this.lastTimeTick = System.currentTimeMillis();
        this.tickInterval = 500 + (Math.abs(id) % 1200); // phân tán: 500-1700ms

        setupStats();
        setupSkills();
    }

    /**
     * Gán stats (sức mạnh, máu, đánh) theo tier ngẫu nhiên.
     * Bot cày chậm hơn Player thật (catchupPercent).
     */
    private void setupStats() {
        int tier = 1 + Math.abs((int) id) % 20; // 1-20
        long power;
        long hpg;
        long dameg;
        switch (tier) {
            case 1: case 2: case 3:
                power = Util.nextLong(1_200L, 100_000L); hpg = Util.nextInt(1000, 2000); dameg = Util.nextInt(50, 150); break;
            case 4: case 5:
                power = Util.nextLong(100_000L, 1_000_000L); hpg = Util.nextInt(2000, 4000); dameg = Util.nextInt(150, 300); break;
            case 6: case 7: case 8:
                power = Util.nextLong(1_000_000L, 10_000_000L); hpg = Util.nextInt(5000, 12000); dameg = Util.nextInt(400, 800); break;
            case 9: case 10: case 11:
                power = Util.nextLong(10_000_000L, 100_000_000L); hpg = Util.nextInt(15000, 30000); dameg = Util.nextInt(1000, 2500); break;
            case 12: case 13: case 14:
                power = Util.nextLong(100_000_000L, 1_000_000_000L); hpg = Util.nextInt(40000, 80000); dameg = Util.nextInt(3000, 6000); break;
            case 15: case 16: case 17:
                power = Util.nextLong(1_000_000_000L, 10_000_000_000L); hpg = Util.nextInt(100000, 250000); dameg = Util.nextInt(8000, 15000); break;
            default:
                power = Util.nextLong(10_000_000_000L, 40_000_000_000L); hpg = Util.nextInt(300000, 600000); dameg = Util.nextInt(20000, 50000); break;
        }

        // Áp catchup: bot không được vượt player (xử lý động ở phase 7)
        this.nPoint.limitPower = nro.player.NPoint.MAX_LIMIT;
        this.nPoint.power = power;
        this.nPoint.tiemNang = power;
        this.nPoint.hpg = hpg;
        this.nPoint.hp = hpg;
        this.nPoint.hpMax = hpg;
        this.nPoint.dameg = dameg;
        this.nPoint.dame = dameg;
        this.nPoint.mpg = hpg / 2;
        this.nPoint.mp = hpg / 2;
        this.nPoint.mpMax = hpg / 2;
        this.nPoint.defg = Util.nextInt(50, 500);
        this.nPoint.def = this.nPoint.defg;
        this.nPoint.critg = 5;
        this.nPoint.crit = 5;
    }

    /**
     * Gán skill theo giới tính (giống player thật).
     */
    private void setupSkills() {
        int point = 1;
        long p = this.nPoint.power;
        if (p < 10_000_000) point = 1;
        else if (p < 50_000_000) point = 2;
        else if (p < 150_000_000) point = 3;
        else if (p < 500_000_000) point = 4;
        else if (p < 1_500_000_000) point = 5;
        else if (p < 20_000_000_000L) point = 6;
        else point = 7;

        this.playerSkill.skills.clear();
        switch (this.gender) {
            case 0:
                addSkill(0, point); addSkill(1, point); addSkill(6, point); addSkill(9, point);
                addSkill(10, point); addSkill(20, point); addSkill(22, point); addSkill(19, point);
                addSkill(17, point);
                break;
            case 1:
                addSkill(2, point); addSkill(3, point); addSkill(7, point); addSkill(11, point);
                addSkill(12, point); addSkill(18, point); addSkill(19, point);
                break;
            default:
                addSkill(4, point); addSkill(5, point); addSkill(8, point); addSkill(13, point);
                addSkill(14, point); addSkill(21, point); addSkill(23, point); addSkill(19, point);
                break;
        }
        if (!this.playerSkill.skills.isEmpty()) {
            this.playerSkill.skillSelect = this.playerSkill.skills.get(0);
        }
    }

    private void addSkill(int skillId, int point) {
        try {
            this.playerSkill.skills.add(Utils.SkillUtil.createSkill(skillId, point));
        } catch (Exception ignored) {}
    }

    public VirtualBrain getBrain() { return brain; }
    public VirtualEquipment getEquipment() { return equipment; }

    /**
     * Tick chính của Virtual Player. Được gọi từ VirtualPlayerManager.
     */
    public void tick() {
        if (!active) return;
        long now = System.currentTimeMillis();

        // Đang OFFLINE: chờ đủ offlineDuration rồi LOGIN LẠI chính nhân vật này
        // (giữ nguyên level/power/memory/quan hệ — không tạo bot mới thay thế)
        if (state == VirtualState.OFFLINE) {
            if (now - timeOffline >= profile.offlineDuration) {
                timeSpawned = now;
                state = VirtualState.SPAWN; // Brain sẽ joinMap lại
            }
            return;
        }

        if (now - lastTimeTick < tickInterval * (lodFar ? 3 : 1)) return;
        lastTimeTick = now;

        // Cập nhật nhu cầu
        needs.update();

        // Hết giờ chơi -> logout (lưu trạng thái, sau này login lại)
        if (now - timeSpawned > profile.onlineDuration) {
            goOffline();
            return;
        }

        // Chuyển cho Brain xử lý quyết định & hành động
        if (brain != null) {
            brain.update();
        }
    }

    /**
     * Logout: thoát khỏi map, vào trạng thái OFFLINE, đợi offlineDuration rồi quay lại.
     */
    private void goOffline() {
        state = VirtualState.OFFLINE;
        timeOffline = System.currentTimeMillis();
        // Kết thúc lượt ghé thăm (nếu có) khi logout -> lần login lại sẽ spawn độc lập
        presenceHostId = 0;
        presenceEndTime = 0;
        try {
            nro.services.Fun.ChangeMapService.gI().exitMap(this);
        } catch (Exception ignored) {}
    }

    /**
     * Đưa VP vào một map/zone với vị trí cụ thể.
     */
    public void joinMap(int mapId, int x, int y) {
        try {
            nro.map.Zone zone = nro.services.MapService.gI().getMapCanJoin(this, mapId, -1);
            if (zone == null) return;
            joinZone(zone, x, y);
        } catch (Exception e) {
            // bỏ qua nếu map không hợp lệ
        }
    }

    /**
     * Vào đúng ZONE chỉ định (dùng khi muốn đứng cùng khu với player thật).
     * @return true nếu vào zone thành công
     */
    public boolean joinZone(nro.map.Zone target, int x, int y) {
        try {
            if (target == null || target.map == null) return false;
            nro.services.Fun.ChangeMapService.gI().goToMap(this, target);
            this.location.x = x;
            this.location.y = target.map.yPhysicInTopBot(x, y);
            nro.services.Service.gI().setPos(this, this.location.x, this.location.y);
            target.load_Me_To_Another(this);
            state = VirtualState.IDLE;
            return true;
        } catch (Exception e) {
            return false;
        }
    }

    public boolean isOnline() {
        return active && state != VirtualState.OFFLINE && zone != null;
    }

    /**
     * Bot có đang trong lượt "ghé thăm" khu người chơi không (còn hạn thời gian).
     * Khi true: bot ở lại khu host, KHÔNG tự đổi map cho tới khi hết lượt.
     */
    public boolean hasActivePresence() {
        return presenceHostId != 0 && System.currentTimeMillis() < presenceEndTime;
    }

    @Override
    public String toString() {
        return "VP[" + name + "|" + (profile != null ? profile.getPersonalities() : "") + "]";
    }
}
