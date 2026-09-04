package nro.virtualplayer;

import nro.mob.Mob;
import nro.player.Player;
import nro.virtualplayer.core.VirtualGoals;
import nro.virtualplayer.core.VirtualState;
import Utils.Util;

/**
 * Bộ não của Virtual Player - Perception + Decision Engine + State Machine.
 * PHASE 3: quét môi trường, chọn hành động theo Utility AI, chuyển state.
 * PHASE 4: thực hiện movement/combat/explore/rest/shop thật theo từng state.
 *
 * Bot KHÔNG đứng im: di chuyển, tìm quái, đánh, nhặt đồ, đổi map, nghỉ, mua đồ...
 * Luôn nhường quái cho Player thật (REAL PLAYER > BOT).
 */
public class VirtualBrain {

    private final VirtualPlayer vp;
    private VirtualPerception perception;
    private VirtualDecision decision;
    private VirtualMovement movement;
    private VirtualCombat combat;
    private VirtualEconomy economy;
    private VirtualChat chat;
    private VirtualSocial social;

    // Chống lặp hành động quá nhanh / chuyển map liên tục
    private long lastChangeMapTime;
    private long lastAttackTime;
    private long lastWanderTime;
    private long lastSellTime;
    private long lastChatTime;
    private long lastGiftCheck;
    private long lastQuestProgress;
    private long lastLootCheck;
    private long lastAdTime;

    public VirtualBrain(VirtualPlayer vp) {
        this.vp = vp;
        this.perception = new VirtualPerception(vp);
        this.decision = new VirtualDecision(vp, perception);
        this.movement = new VirtualMovement(vp);
        this.combat = new VirtualCombat(vp);
        this.economy = new VirtualEconomy(vp);
        this.chat = new VirtualChat(vp);
        this.social = new VirtualSocial(vp);
    }

    public VirtualPerception getPerception() { return perception; }
    public VirtualDecision getDecision() { return decision; }
    public VirtualCombat getCombat() { return combat; }

    /**
     * Tick quyết định & hành động. Được gọi từ VirtualPlayer.tick().
     */
    public void update() {
        if (vp == null || vp.profile == null) return;

        // Nếu chưa vào map: đang trong lượt ghé thăm -> quay lại khu host;
        // ngược lại spawn ĐỘC LẬP về làng (Brain sẽ tự tản ra map theo sức mạnh)
        if (vp.zone == null && vp.state == VirtualState.SPAWN) {
            if (!VirtualPlayerManager.gI().joinHostZone(vp)) {
                int mapId = 39 + vp.gender;
                vp.joinMap(mapId, 100, 384);
            }
            return;
        }
        if (vp.zone == null) return;

        // Quét môi trường (perception luôn cập nhật mỗi tick)
        perception.scan();

        // LOD: không có player thật trong zone -> bot mô phỏng nhẹ (giãn tick 3x)
        vp.lodFar = perception.getRealPlayerCountInZone() == 0;

        // Chat theo context (chỉ khi có player thật gần, cooldown trong VirtualChat)
        if (Util.canDoWithTime(lastChatTime, 8000)) {
            chat.updateChat();
            lastChatTime = System.currentTimeMillis();
        }

        // Cam xuc: icon + cau noi tren dau (cooldown noi bo 15s trong VirtualEmotion)
        try {
            VirtualEmotion.tick(vp, perception);
        } catch (Exception ignored) {}

        // Giao tin nhan rieng dang cho (VirtualTrade) - trong tick chinh, khong tao thread
        try {
            VirtualTrade.deliverPending(vp);
        } catch (Exception ignored) {}

        // Rao ban dinh ky cho bot TRADER (kenh the gioi, cooldown 2 phut)
        try {
            if (vp.profile.hasPersonality(nro.virtualplayer.core.VirtualPersonality.TRADER)
                    && Util.canDoWithTime(lastAdTime, 120000)) {
                lastAdTime = System.currentTimeMillis();
                VirtualTrade.advertise(vp);
            }
        } catch (Exception ignored) {}

        // Tương tác bot-bot (chào hỏi, friend/rival — cooldown nội bộ 30s)
        try {
            social.update();
        } catch (Exception ignored) {}

        // Trang bị: tự thay đồ tốt hơn từ bag (cooldown nội bộ 2 phút)
        try {
            vp.getEquipment().maybeUpgradeGear();
        } catch (Exception ignored) {}

        // Tặng đồ cho player thật nếu đủ điều kiện (bạn + helpfulness)
        if (!perception.getNearbyRealPlayers().isEmpty()
                && Util.canDoWithTime(lastGiftCheck, 60000)) {
            lastGiftCheck = System.currentTimeMillis();
            try {
                Player nearby = perception.getNearbyRealPlayers().get(
                        Util.nextInt(0, perception.getNearbyRealPlayers().size() - 1));
                vp.getEquipment().checkGiftOpportunity(nearby);
            } catch (Exception ignored) {}
        }

        // Nếu chết -> chờ respawn sau một khoảng thời gian ngắn
        if (vp.isDie()) {
            if (vp.state != VirtualState.DEAD && vp.state != VirtualState.RESPAWN) {
                vp.state = VirtualState.DEAD;
                vp.needs.setHpNeed(100);
            }
            handleDead();
            return;
        }

        // Nếu đang rảnh -> chọn hành động mới bằng Utility AI
        if (vp.state == VirtualState.IDLE || vp.state == VirtualState.SPAWN) {
            VirtualDecision.Action act = decision.chooseAction();
            vp.state = decision.toState(act);
            vp.goals.setShortTerm(mapGoal(act));
        }

        // Xử lý state hiện tại theo máy trạng thái
        switch (vp.state) {
            case FIND_TARGET:
            case ATTACK:
                handleCombat();
                break;
            case MOVE_TO_TARGET:
                handleMoveToTarget();
                break;
            case ESCAPE:
                handleEscape();
                break;
            case HEAL:
                handleHeal();
                break;
            case PICK_ITEM:
                handlePickItem();
                break;
            case EXPLORE:
                handleExplore();
                break;
            case CHANGE_MAP:
                handleChangeMap();
                break;
            case REST:
                handleRest();
                break;
            case SOCIAL:
                vp.needs.satisfySocial();
                vp.state = VirtualState.IDLE;
                break;
            case GO_SHOP:
                handleGoShop();
                break;
            case DO_QUEST:
                handleQuest();
                break;
            case DEAD:
            case RESPAWN:
                handleDead();
                break;
            case IDLE:
            default:
                // Lang thang ngau nhien trong khi doi hanh dong moi (giong nguoi that)
                if (Util.canDoWithTime(lastWanderTime, 1500)) {
                    movement.wander(60);
                    lastWanderTime = System.currentTimeMillis();
                    // Sau khi di chuyen -> tai danh gia hanh dong moi
                    if (Util.isTrue(60, 100)) {
                        VirtualDecision.Action act = decision.chooseAction();
                        vp.state = decision.toState(act);
                        vp.goals.setShortTerm(mapGoal(act));
                    } else {
                        vp.state = VirtualState.IDLE;
                    }
                }
                break;
        }
    }

    // ===== COMBAT / FARM =====
    private void handleCombat() {
        // HP quá thấp -> chạy / hồi máu
        if (combat.shouldRetreat()) {
            vp.state = VirtualState.ESCAPE;
            return;
        }
        if (!combat.isHealthy() && combat.checkAndHeal()) {
            vp.state = VirtualState.HEAL;
            return;
        }

        Mob target = combat.findTarget();
        if (target == null) {
            // Không có quái -> nhặt đồ / đổi map nếu lâu quá
            if (!perception.getNearbyItems().isEmpty()) {
                vp.state = VirtualState.PICK_ITEM;
            } else if (!vp.hasActivePresence() && Util.canDoWithTime(lastChangeMapTime, 60000)) {
                vp.state = VirtualState.CHANGE_MAP;
            } else {
                vp.state = VirtualState.IDLE;
            }
            return;
        }

        combat.setTarget(target);
        int dist = Util.getDistance(vp, target);
        if (dist > vp.profile.attackRange) {
            vp.state = VirtualState.MOVE_TO_TARGET;
        } else {
            // Trong tầm -> tấn công (có reaction time + random để giống người)
            if (Util.canDoWithTime(lastAttackTime, 600 + (long) (vp.profile.reactionDelay % 800))) {
                combat.attackTarget();
                lastAttackTime = System.currentTimeMillis();
                // Quest progress: khi dang danh quai va co nhiem vu, tang count
                try {
                    nro.virtualplayer.VirtualQuest q = new nro.virtualplayer.VirtualQuest(vp);
                    nro.task.TaskMain t = q.getCurrentTask();
                    if (t != null && t.subTasks != null && t.index < t.subTasks.size()) {
                        nro.task.SubTaskMain s = t.subTasks.get(t.index);
                        if (s != null && Util.canDoWithTime(lastQuestProgress, 5000)) {
                            s.count++;
                            lastQuestProgress = System.currentTimeMillis();
                            if (s.count >= s.maxCount) {
                                t.index++;
                                if (t.index >= t.subTasks.size()) {
                                    int nid = t.id + 1;
                                    if (t.id == 3) nid = vp.gender + 4;
                                    else if (t.id >= 4 && t.id <= 6) nid = 7;
                                    nro.task.TaskMain nx = nro.services.TaskService.gI().getTaskMainById(vp, nid);
                                    if (nx != null) vp.playerTask.taskMain = nx;
                                }
                            }
                        }
                    }
                } catch (Exception ignored) {}
                // Farm mô phỏng: thi thoảng nhặt được đồ hiếm khi đánh quái
                if (Util.canDoWithTime(lastLootCheck, 5000)) {
                    economy.maybeGainRareItem();
                    lastLootCheck = System.currentTimeMillis();
                }
            }
        }
    }

    private void handleMoveToTarget() {
        Mob target = combat.getTargetMob();
        if (target == null || target.isDie()) {
            vp.state = VirtualState.FIND_TARGET;
            return;
        }
        int dist = Util.getDistance(vp, target);
        if (dist <= vp.profile.attackRange) {
            vp.state = VirtualState.ATTACK;
            return;
        }
        // Di chuyển tới quái (đôi khi đi lệch chút cho tự nhiên)
        int step = 40;
        int nx = target.location.x + Util.nextInt(-20, 20);
        int ny = target.location.y;
        movement.stepToward(nx, ny, step);
        vp.state = VirtualState.ATTACK;
    }

    private void handleEscape() {
        // Chạy ngược lại, ra xa quái, hoặc đổi map nếu quá nguy hiểm
        if (combat.shouldRetreat() && Util.canDoWithTime(lastWanderTime, 3000)) {
            movement.wander(120);
            lastWanderTime = System.currentTimeMillis();
        } else {
            vp.state = VirtualState.IDLE;
        }
        if (combat.getTargetMob() != null && !combat.getTargetMob().isDie()) {
            combat.setTarget(null);
        }
    }

    private void handleHeal() {
        // Đã hồi máu xong -> quay lại chiến đấu
        if (combat.isHealthy()) {
            vp.state = VirtualState.FIND_TARGET;
        } else if (Util.canDoWithTime(lastWanderTime, 2500)) {
            movement.wander(80);
            lastWanderTime = System.currentTimeMillis();
        }
    }

    // ===== EXPLORE =====
    private void handleExplore() {
        vp.needs.satisfyExplore();
        if (Util.canDoWithTime(lastWanderTime, 3000 + vp.profile.reactionDelay % 2000)) {
            movement.wander(150);
            lastWanderTime = System.currentTimeMillis();
        }
        // Lang thang một lúc rồi làm việc khác (xác suất đổi map theo config).
        // Đang ghé thăm khu player -> KHÔNG đổi map, ở lại tương tác.
        float rate = VirtualConfig.gI().mapChangeRate;
        if (!vp.hasActivePresence() && Util.isTrue((int) (10 + rate * 20), 100) && Util.canDoWithTime(lastChangeMapTime, 90000)) {
            vp.state = VirtualState.CHANGE_MAP;
        } else {
            vp.state = VirtualState.IDLE;
        }
    }

    // ===== CHANGE MAP =====
    private void handleChangeMap() {
        // Đang trong lượt ghé thăm khu người chơi -> KHÔNG tự đổi map (ở lại tương tác)
        if (vp.hasActivePresence()) {
            vp.state = VirtualState.IDLE;
            return;
        }
        // map_change_rate cao -> cooldown ngắn (hay đi hơn)
        float rate = VirtualConfig.gI().mapChangeRate;
        long cooldown = (long) (90000 - rate * 60000); // 30s..90s
        if (!Util.canDoWithTime(lastChangeMapTime, cooldown)) {
            vp.state = VirtualState.IDLE;
            return;
        }
        lastChangeMapTime = System.currentTimeMillis();

        int current = vp.zone != null ? vp.zone.map.mapId : -1;
        int newMap = pickMapByPowerSpread();
        if (newMap == current) {
            newMap = 39 + vp.gender; // về town nếu trùng
        }
        try {
            vp.joinMap(newMap, 100, 384);
        } catch (Exception e) {
            // map không hợp lệ -> về town
        }
        vp.state = VirtualState.IDLE;
    }

    /**
     * Chon map theo power + gian deu bot (toi da 4 bot/map, thu lai 3 lan).
     * Port y tuong bots_per_map ben Hashirama.
     */
    private int pickMapByPowerSpread() {
        int pick = pickMapByPower();
        try {
            java.util.Map<Integer, Integer> perMap = new java.util.HashMap<>();
            for (VirtualPlayer o : new java.util.ArrayList<>(VirtualPlayerManager.gI().getBots())) {
                if (o == null || o == vp || o.zone == null || o.zone.map == null) continue;
                int m = o.zone.map.mapId;
                perMap.put(m, perMap.getOrDefault(m, 0) + 1);
            }
            for (int t = 0; t < 3 && perMap.getOrDefault(pick, 0) >= 4; t++) {
                pick = pickMapByPower();
            }
        } catch (Exception ignored) {}
        return pick;
    }

    /**
     * Chọn map phù hợp sức mạnh (giống cách Bot thật chọn map theo power).
     * Tránh để toàn bộ bot dồn 1 map.
     */
    private int pickMapByPower() {
        long power = vp.nPoint.power;
        int mapId;
        if (power < 16_000) {
            mapId = new int[]{0, 1, 2, 9, 16, 7, 8, 14, 15}[Util.nextInt(0, 8)];
        } else if (power < 100_000) {
            mapId = new int[]{3, 4, 11, 12, 17, 18}[Util.nextInt(0, 5)];
        } else if (power < 500_000) {
            mapId = new int[]{27, 28, 31, 32, 35, 36}[Util.nextInt(0, 5)];
        } else if (power < 1_500_000) {
            mapId = new int[]{5, 29, 30, 13, 33, 34, 20, 37, 38}[Util.nextInt(0, 8)];
        } else if (power < 50_000_000) {
            mapId = new int[]{6, 10, 19}[Util.nextInt(0, 2)];
        } else if (power < 200_000_000) {
            mapId = new int[]{68, 69, 70}[Util.nextInt(0, 2)];
        } else if (power < 500_000_000) {
            mapId = new int[]{71, 72, 64, 65}[Util.nextInt(0, 3)];
        } else if (power < 1_000_000_000) {
            mapId = new int[]{63, 66, 67}[Util.nextInt(0, 2)];
        } else if (power < 5_000_000_000L) {
            mapId = new int[]{73, 74, 75, 76}[Util.nextInt(0, 3)];
        } else if (power < 10_000_000_000L) {
            mapId = new int[]{77, 81, 82}[Util.nextInt(0, 2)];
        } else if (power < 25_000_000_000L) {
            mapId = new int[]{83, 79, 80}[Util.nextInt(0, 2)];
        } else {
            mapId = new int[]{92, 93, 94, 96, 97}[Util.nextInt(0, 4)];
        }
        return mapId;
    }

    // ===== REST =====
    private void handleRest() {
        vp.needs.satisfyRest();
        // Nghỉ hồi HP/MP từ từ
        try {
            if (vp.nPoint.hp < vp.nPoint.hpMax) {
                vp.nPoint.hp = Math.min(vp.nPoint.hpMax, vp.nPoint.hp + vp.nPoint.hpMax / 20);
            }
        } catch (Exception ignored) {}
        vp.state = VirtualState.IDLE;
    }

    // ===== SHOP =====
    private void handleGoShop() {
        // Bán đồ rác + nạp potion nếu cần
        if (Util.canDoWithTime(lastSellTime, 120000)) {
            economy.sellTrash();
            lastSellTime = System.currentTimeMillis();
        }
        // Ký gửi đồ hiếm lên chợ Bông
        economy.consignRareItems();
        vp.needs.satisfyItem(25);
        // Về town mua đậu thần nếu cần
        if (!economy.hasPotion()) {
            try {
                vp.joinMap(39 + vp.gender, 100, 384);
            } catch (Exception ignored) {}
        }
        vp.state = VirtualState.IDLE;
    }

    // ===== QUEST =====
    private void handleQuest() {
        nro.virtualplayer.VirtualQuest quest = new nro.virtualplayer.VirtualQuest(vp);
        nro.task.TaskMain task = quest.getCurrentTask();
        if (task == null || task.subTasks == null || task.subTasks.isEmpty()) {
            vp.state = VirtualState.FIND_TARGET;
            return;
        }

        // Điều hướng đến map mục tiêu nếu chưa ở đó (nhưng KHÔNG rời khu nếu đang ghé thăm player)
        int objMap = quest.getObjectiveMap();
        if (objMap >= 0 && vp.zone != null && vp.zone.map != null && vp.zone.map.mapId != objMap) {
            if (!vp.hasActivePresence() && Util.canDoWithTime(lastChangeMapTime, 10000)) {
                try {
                    vp.joinMap(objMap, 100 + (int)(Math.random() * 200), 384);
                } catch (Exception ignored) {}
                lastChangeMapTime = System.currentTimeMillis();
            }
            vp.state = VirtualState.IDLE;
            return;
        }

        // Tiến triển nhiệm vụ trực tiếp (bypass network sends)
        try {
            nro.task.SubTaskMain sub = task.subTasks.get(task.index);
            if (sub != null && Util.canDoWithTime(lastQuestProgress, 5000)) {
                sub.count++;
                lastQuestProgress = System.currentTimeMillis();
                if (sub.count >= sub.maxCount) {
                    task.index++;
                    if (task.index >= task.subTasks.size()) {
                        // Main task hoàn thành → chuyển task mới
                        int nextId = task.id + 1;
                        if (task.id == 3) nextId = vp.gender + 4;
                        else if (task.id == 4 || task.id == 5 || task.id == 6) nextId = 7;
                        try {
                            nro.task.TaskMain next = nro.services.TaskService.gI().getTaskMainById(vp, nextId);
                            if (next != null) vp.playerTask.taskMain = next;
                        } catch (Exception ignored) {}
                    }
                }
            }
        } catch (Exception ignored) {}

        vp.needs.satisfyQuest();
        vp.state = VirtualState.IDLE;
    }

    // ===== DEAD / RESPAWN =====
    private void handleDead() {
        // Chờ 5-15s rồi hồi sinh tại town
        if (Util.canDoWithTime(lastWanderTime, 5000 + vp.profile.reactionDelay % 10000)) {
            try {
                nro.services.Service.gI().hsChar(vp, vp.nPoint.hpMax, vp.nPoint.mpMax);
            } catch (Exception e) {
                // Fallback: tự hồi phục nếu hsChar lỗi
                try {
                    vp.nPoint.hp = vp.nPoint.hpMax;
                    vp.nPoint.mp = vp.nPoint.mpMax;
                } catch (Exception ignored) {}
            }
            try {
                // Đang ghé thăm -> hồi sinh lại đúng khu host; nếu không -> về làng (độc lập)
                if (!VirtualPlayerManager.gI().joinHostZone(vp)) {
                    vp.joinMap(39 + vp.gender, 100, 384);
                }
            } catch (Exception ignored) {}
            lastWanderTime = System.currentTimeMillis();
            vp.needs.setHpNeed(0);
            vp.state = VirtualState.IDLE;
        }
    }

    // ===== PICK ITEM =====
    private void handlePickItem() {
        nro.map.ItemMap item = perception.getNearestItem();
        if (item != null) {
            int dist = Util.getDistance(vp.location.x, vp.location.y, item.x, item.y);
            if (dist > 40) {
                movement.stepToward(item.x, item.y, 50);
            } else {
                try {
                    vp.zone.pickItem(vp, item.itemMapId);
                    vp.needs.satisfyItem(15);
                } catch (Exception ignored) {}
            }
        }
        vp.state = VirtualState.IDLE;
    }

    private VirtualGoals.ShortTermGoal mapGoal(VirtualDecision.Action act) {
        switch (act) {
            case FARM: return VirtualGoals.ShortTermGoal.KILL_MONSTERS;
            case QUEST: return VirtualGoals.ShortTermGoal.DO_QUEST;
            case EXPLORE: return VirtualGoals.ShortTermGoal.EXPLORE;
            case REST: return VirtualGoals.ShortTermGoal.REST;
            case GO_SHOP: return VirtualGoals.ShortTermGoal.SHOP;
            case SOCIALIZE: return VirtualGoals.ShortTermGoal.SOCIALIZE;
            case CHANGE_MAP: return VirtualGoals.ShortTermGoal.CHANGE_MAP;
            case HUNT_BOSS: return VirtualGoals.ShortTermGoal.HUNT_BOSS;
            default: return vp.goals.getShortTerm();
        }
    }
}
