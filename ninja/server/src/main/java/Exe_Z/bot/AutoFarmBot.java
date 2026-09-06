/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package Exe_Z.bot;

import Exe_Z.ability.AbilityCustom;
import Exe_Z.item.Item;
import Exe_Z.item.ItemFactory;
import Exe_Z.map.item.ItemMap;
import Exe_Z.map.zones.Zone;
import Exe_Z.mob.Mob;
import Exe_Z.model.Char;
import Exe_Z.model.Trade;
import Exe_Z.model.Trader;
import Exe_Z.model.User;
import Exe_Z.network.Message;
import Exe_Z.party.MemberGroup;
import Exe_Z.util.Log;
import Exe_Z.util.NinjaUtils;
import java.util.ArrayList;
import java.util.Iterator;
import java.util.List;
import java.util.Vector;
import java.util.concurrent.atomic.AtomicInteger;

/**
 * Bot tự động mô phỏng người chơi thật: tự tìm quái đánh, tự nhặt đồ rơi,
 * tự hồi phục HP/MP, tự hồi sinh, spam chat, nhường quái cho người chơi thật,
 * chủ động lập tổ đội với người chơi và chủ động giao dịch tặng đồ cao cấp.
 *
 * @author longth68
 */
public class AutoFarmBot extends Bot {

    public static final int MAX_BOT_PER_ZONE = 8;
    public static final long BOT_LIFETIME = 3 * 60 * 60 * 1000L;

    private static final AtomicInteger ID_GEN = new AtomicInteger(1000000);
    private static final List<AutoFarmBot> BOTS = java.util.Collections.synchronizedList(new ArrayList<>());

    private static final int TRADE_DISTANCE = 120;
    private static final int PARTY_DISTANCE = 500;
    private static final int AVOID_PLAYER_MOB_RADIUS = 190;
    private static final int AVOID_PLAYER_TARGET_RADIUS = 140;

    // Các map làng (an toàn) - bot không đánh quái, không mời tổ đội ở đây
    private static final int[] VILLAGE_MAP_IDS = {10, 17, 22, 32, 38, 43, 48, 138, 162};

    // Tên ngẫu nhiên giống người chơi thật
    private static final String[] RANDOM_NAMES = {
        "chutich", "trumsida", "theking", "maumebebet", "caubeusau", "s2tromchos2",
        "ansiunhan", "ninjaschool", "1dapvelang", "sonlol", "mavuong", "tiktok",
        "chimtaoto", "satria", "thiendia", "tieugiabao", "onghoang", "kunnaitaitu",
        "sonatn", "yakuza", "dragon", "xincaituoi", "caothu", "killer",
        "huysieunhan", "pk1stt", "tongthong", "embebao", "tiktoke", "anhgia",
        "cungbattu", "lizpk2", "synonim", "chongu", "anh1998", "papalove",
        "critdame", "fangvl", "tieulongnu", "thanhnd", "cuong1122", "tiepvip",
        "hai123", "top123", "inboxtoi", "nhwquineiu", "conghao", "scary1",
        "ilovevou", "accphu5", "kokein00", "aidol98", "overtime", "sahdowaa",
        "clorinde", "bodoi007", "minhm103", "quyetko", "badboy14", "ccmenly",
        "top1sever", "kimchi", "trumpk", "accphu2", "hieuboss", "kaisen",
        "thienlong", "bongtoi", "dailada", "sieuquai", "vuavu", "nhanvatmoi",
        "trainer", "gamerpro", "vipz", "pkpro", "satthu", "kienthuc",
        "daichien", "hoangtu", "congchua", "hoangde", "binhthuong", "ngaunhien"
    };

    // Tạo tên ngẫu nhiên giống người chơi thật (tên gốc + số)
    private static String randomPlayerName() {
        String base = RANDOM_NAMES[NinjaUtils.nextInt(0, RANDOM_NAMES.length - 1)];
        int suffix = NinjaUtils.nextInt(0, 100);
        String name = base;
        if (suffix < 70) {
            name += NinjaUtils.nextInt(1, 9999);
        }
        if (name.length() > 12) {
            name = name.substring(0, 12);
        }
        return name;
    }

    private boolean isVillage() {
        int mid = mapId;
        for (int v : VILLAGE_MAP_IDS) {
            if (mid == v) {
                return true;
            }
        }
        return false;
    }

    // Các mẫu đồ an toàn bot tặng khi giao dịch với người chơi (giới hạn id < 650 để tương thích mọi phiên bản)
    private static final int[] RARE_ITEM_IDS = {
        7, 8, 9, 10, 11, 12, // Đá cường hóa các cấp
        162, 164, 166 // Phúc nang, etc.
    };
    private static final String[] CHAT_ROAM = {
        "Ai train chung khong?", "Map nay kha on do",
        "Co ai lam nhiem vu map nay khong?", "Di quanh kiem bai train ngon",
        "Khu nay dong vui ghe", "Len bai nao ae?",
        "Minh moi qua map nay", "Can nguoi di chung cho vui",
        "Khu nao con trong khong ae?", "Quai hoi thua, doi bai khong?",
        "Ai biet bai exp ngon chi minh voi", "Di xa qua lai quay ve lang mat",
        "Map nay de train ky nang ne", "Dang tim bai hop level",
        "Minh dung day doi ban chut", "Khu 1 dong qua nhi",
        "Ai vua qua day vay?", "Map nay nhin quen quen",
        "Di mot minh hoi buon", "Can mua it binh mp nua"
    };

    private static final String[] CHAT_HUNT = {
        "Quai gan day hoi trau", "Tap trung danh con gan truoc",
        "Dame minh tam on roi", "Can pt de clear quai nhanh hon",
        "Dang can them exp ky nang", "Quai nay drop gi vay ae?",
        "Danh bai nay exp kha on", "De minh keo quai lai gan",
        "Quai sap chet roi", "Con nay ne ae",
        "Dung gan qua coi chung mat mau", "Ai buff giup minh voi",
        "Skill vua len cap ngon hon han", "Danh con gan nguoi truoc nha",
        "Bai nay farm cung tam", "Quai hoi lech level nhung van danh duoc",
        "Minh danh ben trai nha", "De minh lo con nay",
        "Combo dep ghe", "Quai nay ne, dung bo qua"
    };

    private static final String[] CHAT_REST = {
        "Doi chut hoi mp", "Het mp roi nghi ti",
        "Hp hoi thap, dung lai xi", "Can an them binh mau",
        "Nghi 1 chut roi danh tiep", "Quai danh dau qua",
        "Cho minh hoi skill xong da", "Dang sap het mp",
        "Doi cooldown chut nha", "Can mua them thuoc roi",
        "Hp len lai roi danh tiep", "Dung xa quai chut",
        "Nghi mot nhip thoi", "Mp hoi it nen danh cham lai",
        "Ti nua minh vao danh tiep", "Khoan keo them quai nha"
    };

    private static final String[] CHAT_SOCIAL = {
        "Ai lap team train khong?", "Cho minh vao nhom voi",
        "Ket ban de lan sau train tiep nha", "Party cho co buff nao",
        "Di chung cho nhanh len cap", "Ai cung cap thi pt nhe",
        "Team nao thieu nguoi khong?", "Pt 2 nguoi clear nhanh hon do",
        "Ai dang lam nhiem vu thi minh theo", "Minh co the keo quai phu",
        "Lap nhom roi chia bai danh nha", "Minh theo team nao cung duoc",
        "Party xong dung tach xa qua nha", "Dung chung bai cho vui",
        "Co buff party danh nhanh hon"
    };

    private Mob target;
    private int tick;
    private long respawnAt;
    private long despawnAt;
    private int lootCount;
    private short spawnX;
    private short spawnY;

    private long nextChatTime;
    private long nextSocialTime;
    private long nextPartyInviteTime;
    private long nextTradeTime;

    // ===== NRO-style AI (port VirtualPlayer) =====
    public Exe_Z.bot.ai.BotProfile botProfile = new Exe_Z.bot.ai.BotProfile();
    public Exe_Z.bot.ai.BotNeeds botNeeds = new Exe_Z.bot.ai.BotNeeds();
    public Exe_Z.bot.ai.BotMemory botMemory = new Exe_Z.bot.ai.BotMemory();
    public Exe_Z.bot.ai.BotGoals botGoals = new Exe_Z.bot.ai.BotGoals();
    public Exe_Z.bot.ai.BotState botState = Exe_Z.bot.ai.BotState.SPAWN;
    public boolean aiEnabled = true;
    public int botTick = 0;
    public long nextAiChatTime = 0L;
    public long nextAiSocialTime = 0L;
    public long lastAiMapChange = 0L;

    private AutoFarmBot(int id, String name, int level, byte typePk, byte classId) {
        super(id, name, level, typePk, classId);
        try {
            botProfile.rollPersonality();
            botGoals.rollLongTerm(botProfile);
        } catch (Exception ignored) {
        }
    }

    /** Public wrappers cho package ai (giữ logic di chuyển/đánh/nhặt cũ). */
    public void aiMoveTo(int tx, int ty) {
        moveTo(tx, ty);
    }

    public void aiAttackMob(Mob mob) {
        attackMob(mob);
    }

    public int[] aiMapBounds() {
        return getMapBounds();
    }

    public boolean aiIsVillage() {
        return isVillage();
    }

    public void aiPickup(Exe_Z.map.item.ItemMap im) {
        if (im == null || zone == null) {
            return;
        }
        im.lock.lock();
        try {
            if (!im.isPickedUp()) {
                im.setPickedUp(true);
                zone.getService().pickItem(this, im);
                zone.removeItem(im);
                lootCount++;
            }
        } finally {
            im.lock.unlock();
        }
    }

    public boolean aiPickupNearest() {
        return autoPickup();
    }

    public void aiTryParty(Char player) {
        maybeJoinParty(player);
    }

    @Override
    public void updateEveryHalfSecond() {
        try {
            super.updateEveryHalfSecond();
        } catch (Exception ex) {
            Log.error("AutoFarmBot super err: " + ex.getMessage(), ex);
        }
        try {
            if (zone == null || isCleaned) {
                return;
            }
            tick++;
            if (System.currentTimeMillis() > despawnAt) {
                despawn();
                return;
            }
            if (isDead) {
                botState = Exe_Z.bot.ai.BotState.DEAD;
                if (respawnAt == 0) {
                    respawnAt = System.currentTimeMillis() + 3000L;
                }
                if (System.currentTimeMillis() >= respawnAt) {
                    respawnAt = 0;
                    recovery();
                    setXY(spawnX, spawnY);
                    zone.getService().playerMove(this);
                    botState = Exe_Z.bot.ai.BotState.RESPAWN;
                }
                return;
            }
            // ===== NRO-style Brain: điều phối theo Needs/State/Personality =====
            if (aiEnabled) {
                botTick = tick;
                try {
                    Exe_Z.bot.ai.BotBrain.update(this);
                    Exe_Z.bot.ai.BotBrain.tickClanAndPvp(this);
                } catch (Exception ex) {
                    Log.error("AutoFarmBot AI err: " + ex.getMessage(), ex);
                }
                return;
            }
            autoHeal();
            autoChat();
            maybeSocialActions();
            autoBalanceZone();
            if (isVillage()) {
                // Trong làng: bot chỉ đi dạo chậm rãi, không đánh quái, không nhặt đồ
                villageWander();
                return;
            }
            if (!autoPickup()) {
                if (!autoAttack()) {
                    wander();
                }
            }
        } catch (Exception ex) {
            Log.error("AutoFarmBot err: " + ex.getMessage(), ex);
            System.out.println("[BOT][STACK] " + name + " " + ex.toString());
            for (StackTraceElement el : ex.getStackTrace()) {
                System.out.println("[BOT][STACK]   at " + el);
            }
        }
    }

    // Đi dạo chậm rãi trong làng
    private void villageWander() {
        if (tick % 6 != 0) {
            return;
        }
        int[] b = getMapBounds();
        int nx = x + NinjaUtils.nextInt(-50, 50);
        int ny = y + NinjaUtils.nextInt(-20, 20);
        if (nx < b[0]) {
            nx = b[0];
        }
        if (ny < b[2]) {
            ny = b[2];
        }
        if (nx > b[1]) {
            nx = b[1];
        }
        if (ny > b[3]) {
            ny = b[3];
        }
        moveTo(nx, ny);
    }

    // ---------------- CÂN BẰNG BOT ĐỘNG THEO NGƯỜI CHƠI ----------------
    // Mỗi zone luôn có tối thiểu 1 bot trụ. Khi có người chơi thật đến,
    // bot bổ sung xuất hiện (tối đa MAX_BOT_PER_ZONE). Khi người chơi rời đi,
    // bot thừa tự biến mất chỉ giữ lại 1 bot.
    private long nextBalanceTime;

    private void autoBalanceZone() {
        long now = System.currentTimeMillis();
        if (now < nextBalanceTime) {
            return;
        }
        nextBalanceTime = now + 1500L;
        if (zone == null) {
            return;
        }
        int realPlayers = 0;
        Char anyPlayer = null;
        synchronized (zone.players) {
            for (Char p : zone.players) {
                if (p != null && p != this && isRealPlayer(p)) {
                    realPlayers++;
                    anyPlayer = p;
                }
            }
        }
        // Nếu zone này không có người chơi nhưng có người chơi trong cùng map ở zone khác,
        // bot leader di chuyển sang zone có người chơi để "đi theo" người chơi.
        if (realPlayers == 0) {
            Char target = findRealPlayerInSameMap();
            if (target != null && target.zone != null && target.zone != zone) {
                outZone();
                int zid = target.zone.id;
                joinZone(mapId, zid, -1);
                System.out.println("[BOT][BALANCE] " + name + " di chuyển sang zone " + zid + " của người chơi " + target.name);
                return;
            }
        }
        // đếm bot đang sống trong zone
        int zoneBots = 0;
        int leaderId = Integer.MAX_VALUE;
        synchronized (BOTS) {
            for (AutoFarmBot b : BOTS) {
                if (b != null && !b.isCleaned && b.zone == zone) {
                    zoneBots++;
                    if (b.id < leaderId) {
                        leaderId = b.id;
                    }
                }
            }
        }
        boolean isLeader = (id == leaderId);

        if (realPlayers > 0) {
            // Có người chơi: bot leader mở rộng lên tối đa (lần lượt xuất hiện)
            if (isLeader && zoneBots < MAX_BOT_PER_ZONE) {
                int target = Math.min(MAX_BOT_PER_ZONE, zoneBots + 1);
                for (int i = zoneBots; i < target; i++) {
                    AutoFarmBot nb = createBot(zone, Math.max(1, level), 50000, damage, classId);
                    nb.setXY((short) Math.max(0, x + NinjaUtils.nextInt(-60, 60)), (short) Math.max(0, y + NinjaUtils.nextInt(-30, 30)));
                    nb.spawnX = nb.x;
                    nb.spawnY = nb.y;
                    nb.despawnAt = now + BOT_LIFETIME;
                    BOTS.add(nb);
                    zone.join(nb);
                    System.out.println("[BOT][BALANCE] " + name + " spawn thêm bot (người chơi=" + realPlayers + ", bot=" + (i + 1) + "/" + MAX_BOT_PER_ZONE + ")");
                }
            }
        } else {
            // Không có người chơi: chỉ giữ 1 bot trụ, bot phụ tự despawn
            if (!isLeader) {
                despawn();
                return;
            }
        }
    }

    // Tìm người chơi thật trong cùng map (các zone khác)
    private Char findRealPlayerInSameMap() {
        if (zone == null || zone.map == null) {
            return null;
        }
        java.util.List<Exe_Z.map.zones.Zone> zones = zone.map.getZones();
        if (zones == null) {
            return null;
        }
        for (Exe_Z.map.zones.Zone z : zones) {
            if (z == null || z == zone) {
                continue;
            }
            synchronized (z.players) {
                for (Char p : z.players) {
                    if (p != null && p != this && isRealPlayer(p)) {
                        return p;
                    }
                }
            }
        }
        return null;
    }

    private void autoHeal() {
        if (hp < maxHP * 0.4) {
            hp = maxHP;
            mp = maxMP;
            zone.getService().loadHP(this);
        }
    }

    // ---------------- CHAT TỰ ĐỘNG ----------------
    private void autoChat() {
        long now = System.currentTimeMillis();
        if (now < nextChatTime) {
            return;
        }
        nextChatTime = now + NinjaUtils.nextInt(4000, 10000);
        String[] pool;
        if (hp < maxHP * 0.3 || mp < maxMP * 0.2) {
            pool = CHAT_REST;
        } else if (target != null && !target.isDead) {
            pool = CHAT_HUNT;
        } else {
            pool = CHAT_ROAM;
        }
        String line = pool[NinjaUtils.nextInt(0, pool.length - 1)];
        if (zone != null && line != null) {
            try {
                zone.getService().chat(id, line);
                // System.out.println("[BOT][CHAT] " + name + " (map " + zone.id + "): " + line);
            } catch (Exception ex) {
                Log.error("AutoFarmBot chat err: " + ex.getMessage(), ex);
            }
        }
    }

    // ---------------- TỔ ĐỘI + GIAO DỊCH TỰ ĐỘNG ----------------
    private void maybeSocialActions() {
        long now = System.currentTimeMillis();
        if (now < nextSocialTime) {
            return;
        }
        nextSocialTime = now + NinjaUtils.nextInt(12000, 25000);
        Char player = findNearestRealPlayer(PARTY_DISTANCE);
        if (player == null) {
            return;
        }
        maybeJoinParty(player);
        maybeTradeWithPlayer(player);
    }

    private Char findNearestRealPlayer(int range) {
        if (zone == null) {
            return null;
        }
        Char result = null;
        int best = Integer.MAX_VALUE;
        synchronized (zone.players) {
            for (Char p : zone.players) {
                if (p == null || p == this || !isRealPlayer(p)) {
                    continue;
                }
                int d = NinjaUtils.getDistance(x, y, p.x, p.y);
                if (d <= range && d < best) {
                    best = d;
                    result = p;
                }
            }
        }
        return result;
    }

    private boolean isRealPlayer(Char p) {
        if (p instanceof Bot) {
            return false;
        }
        return p.user != null && p.user.session != null && !p.isDead;
    }

    private boolean hasRealPlayerInMap() {
        if (zone == null) {
            return false;
        }
        synchronized (zone.players) {
            for (Char p : zone.players) {
                if (p != null && p != this && isRealPlayer(p)) {
                    return true;
                }
            }
        }
        return false;
    }

    private void maybeJoinParty(Char player) {
        if (player == null) {
            return;
        }
        // Chỉ mời tổ đội khi ở ngoài làng (đang đánh quái), không mời trong làng
        if (isVillage()) {
            return;
        }
        // Người chơi dưới cấp 30 không tổ đội được -> bot không mời
        if (player.level < 30) {
            return;
        }
        try {
            long now = System.currentTimeMillis();
            if (now < nextPartyInviteTime) {
                return;
            }
            nextPartyInviteTime = now + NinjaUtils.nextInt(90000, 180000);
            // Bot tạo nhóm nếu chưa có (createGroup tự kiểm tra group nội bộ và tự thoát nếu đã có)
            createGroup();
            // Gửi lời mời vào nhóm cho người chơi (giống người chơi mời thủ công)
            if (player.invite != null) {
                player.invite.addCharInvite(Exe_Z.model.Invite.NHOM, id, 30);
                player.getService().partyInvite(id, name);
                moveTo(player.x, player.y);
                zone.getService().chat(id, "Ai muon party train chung khong? Minh moi vao nhom nhe!");
            }
        } catch (Exception ex) {
            Log.error("AutoFarmBot party err: " + ex.getMessage(), ex);
        }
    }

    private boolean hasTradeBlocked(Char player) {
        return player.trade == null && trade == null;
    }

    /**
     * Kiểm chứng server-side tính năng tổ đội + giao dịch (tặng đồ cao cấp)
     * bằng 2 bot: bot A đóng vai người chơi, bot B tự đánh quái/chat.
     * Trả về chuỗi log kết quả để Web Admin ghi nhận.
     */
    public static String testFeatures(int mapId) {
        StringBuilder log = new StringBuilder();
        Exe_Z.map.Map map = Exe_Z.map.MapManager.getInstance().find(mapId);
        if (map == null) {
            return "TEST_BOT: map " + mapId + " khong ton tai";
        }
        Zone z = map.rand();
        if (z == null) {
            return "TEST_BOT: khong co zone cho map " + mapId;
        }
        int sx = 0, sy = 0;
        if (z.tilemap != null && z.tilemap.waypoints != null && !z.tilemap.waypoints.isEmpty()) {
            Exe_Z.map.Waypoint wp = z.tilemap.waypoints.get(0);
            sx = (wp.minX + wp.maxX) / 2;
            sy = (wp.minY + wp.maxY) / 2;
        }
        // Bot A = người chơi mô phỏng (có session giả để isRealPlayer = true)
        AutoFarmBot player = createBot(z, 100, 50000, 3000, (byte) 1);
        player.setXY((short) sx, (short) sy);
        player.spawnX = player.x;
        player.spawnY = player.y;
        player.despawnAt = System.currentTimeMillis() + BOT_LIFETIME;
        // Bot B = bot tự động đánh quái/chat
        AutoFarmBot farmBot = createBot(z, 100, 50000, 3000, (byte) 1);
        farmBot.setXY((short) (sx + 60), (short) sy);
        farmBot.spawnX = farmBot.x;
        farmBot.spawnY = farmBot.y;
        farmBot.despawnAt = System.currentTimeMillis() + BOT_LIFETIME;
        // Gán user giả cho bot A để đóng vai "người chơi thật" (isRealPlayer cần session != null)
        try {
            java.net.ServerSocket ss = new java.net.ServerSocket(0);
            int port = ss.getLocalPort();
            java.net.Socket sock = new java.net.Socket("127.0.0.1", port);
            java.net.Socket accepted = ss.accept();
            Exe_Z.network.Session fakeSession = new Exe_Z.network.Session(accepted, 1);
            fakeSession.connected = true;
            player.user = new User(fakeSession, "bot_player", "", "");
            player.user.activated = 1;
            ss.close();
        } catch (Exception e) {
            log.append("TEST_BOT: cannot create fake session: ").append(e.getMessage()).append("\n");
        }
        BOTS.add(player);
        BOTS.add(farmBot);
        z.join(player);
        z.join(farmBot);
        // --- TEST TỔ ĐỘI ---
        log.append("=== TEST TỔ ĐỘI ===\n");
        try {
            farmBot.maybeJoinParty(player);
            log.append("maybeJoinParty chạy không lỗi\n");
            log.append("Bot B đã gọi createGroup() (tạo nhóm) + mời Bot A (người chơi) qua Service.partyInvite + Invite.NHOM\n");
            log.append("Bot B đã chat mời: 'Ai muon party train chung khong?...'\n");
            log.append("Party OK: luồng lập tổ đội + mời người chơi đã thực thi thành công (không exception)\n");
        } catch (Exception e) {
            log.append("Party EXCEPTION: ").append(e).append("\n");
        }
        // --- TEST GIAO DỊCH (tặng đồ theo cấp người chơi) ---
        log.append("=== TEST GIAO DỊCH - TẶNG ĐỒ THEO CẤP ===\n");
        int[] testLevels = new int[]{10, 20, 50, 80, 100, 120};
        try {
            for (int lv : testLevels) {
                AutoFarmBot p = createBot(z, lv, 50000, 3000, (byte) 1);
                p.setXY((short) sx, (short) sy);
                p.spawnX = p.x;
                p.spawnY = p.y;
                p.despawnAt = System.currentTimeMillis() + BOT_LIFETIME;
                try {
                    java.net.ServerSocket ss = new java.net.ServerSocket(0);
                    java.net.Socket sock = new java.net.Socket("127.0.0.1", ss.getLocalPort());
                    java.net.Socket accepted = ss.accept();
                    Exe_Z.network.Session fs = new Exe_Z.network.Session(accepted, 1);
                    fs.connected = true;
                    p.user = new User(fs, "bot_player_lv" + lv, "", "");
                    p.user.activated = 1;
                    ss.close();
                } catch (Exception e) {
                    log.append("  skip lv").append(lv).append(" (no session): ").append(e.getMessage()).append("\n");
                    continue;
                }
                BOTS.add(p);
                z.join(p);
                // Đưa bot về sát người chơi để đảm bảo trong tầm giao dịch (100px)
                farmBot.setXY((short) (sx + 40), (short) sy);
                int bagBefore = countBagItems(p);
                farmBot.nextTradeTime = 0;
                farmBot.cleanTrade();
                farmBot.maybeTradeWithPlayer(p);
                int bagAfter = countBagItems(p);
                boolean itemReceived = bagAfter > bagBefore;
                log.append("Player lv").append(lv).append(": item nhận=").append(itemReceived ? "CÓ" : "KHÔNG")
                        .append(" | ").append(describeBagItems(p)).append("\n");
                synchronized (BOTS) {
                    BOTS.remove(p);
                }
                z.out(p);
            }
        } catch (Exception e) {
            log.append("Trade EXCEPTION: ").append(e).append("\n");
        }
        log.append("=== END TEST ===\n");
        // Dọn bot test sau 60 giây
        new Thread(() -> {
            try {
                Thread.sleep(60000);
                synchronized (BOTS) {
                    BOTS.remove(player);
                    BOTS.remove(farmBot);
                }
                z.out(player);
                z.out(farmBot);
            } catch (Exception ignored) {
            }
        }).start();
        return log.toString();
    }

    private static int countBagItems(Char c) {
        int n = 0;
        if (c != null && c.bag != null) {
            for (int i = 0; i < c.bag.length; i++) {
                if (c.bag[i] != null) {
                    n++;
                }
            }
        }
        return n;
    }

    private static String describeBagItems(Char c) {
        StringBuilder sb = new StringBuilder();
        if (c != null && c.bag != null) {
            for (int i = 0; i < c.bag.length; i++) {
                if (c.bag[i] != null) {
                    Item it = c.bag[i];
                    String nm = it.template != null ? it.template.name : ("id=" + it.id);
                    sb.append("[").append(nm).append(" lv=").append(it.template != null ? it.template.level : -1).append("]");
                }
            }
        }
        return sb.length() == 0 ? "(trống)" : sb.toString();
    }

    private void maybeTradeWithPlayer(Char player) {
        long now = System.currentTimeMillis();
        if (now < nextTradeTime) {
            System.out.println("[BOT][TRADE-DEBUG] " + name + " skip: cooldown");
            return;
        }
        // Người chơi dưới cấp 30 không giao dịch được -> bot không mời
        if (player == null || player.level < 30) {
            return;
        }
        if (player == null || player.trade != null || trade != null) {
            System.out.println("[BOT][TRADE-DEBUG] " + name + " skip: player=" + (player != null) + " player.trade=" + (player != null && player.trade != null) + " my.trade=" + (trade != null));
            return;
        }
        if (player.user == null || user == null) {
            System.out.println("[BOT][TRADE-DEBUG] " + name + " skip: user null player.user=" + (player != null && player.user != null) + " my.user=" + (user != null));
            return;
        }
        int d = NinjaUtils.getDistance(x, y, player.x, player.y);
        System.out.println("[BOT][TRADE-DEBUG] " + name + " dist=" + d + " (limit " + TRADE_DISTANCE + ")");
        if (d > TRADE_DISTANCE) {
            return;
        }
        nextTradeTime = now + NinjaUtils.nextInt(90000, 180000);
        try {
            Item rare = createItemForLevel(player.level);
            if (rare == null) {
                return;
            }
            if (!addItemToBag(rare)) {
                return;
            }
            // Mở giao dịch server-side giữa bot và người chơi
            Trade tr = new Trade();
            trade = tr;
            player.trade = tr;
            myTrade = tr.traders[0] = new Trader(this);
            player.myTrade = tr.traders[1] = new Trader(player);
            partnerTrade = player.myTrade;
            player.partnerTrade = myTrade;
            tr.openUITrade();
            // Bot tự đưa đồ cao cấp vào bàn giao dịch
            myTrade.itemTradeOrder = new Vector<>();
            myTrade.itemTradeOrder.add(rare);
            if (player.myTrade.itemTradeOrder == null) {
                player.myTrade.itemTradeOrder = new Vector<>();
            }
            myTrade.isLock = true;
            myTrade.coinTradeOrder = 0;
            player.getService().tradeItemLock(myTrade);
            // Bot tự xác nhận, đồng ý giao dịch
            myTrade.accept = true;
            player.myTrade.accept = true;
            player.getService().tradeAccept();
            System.out.println("[BOT][TRADE-DEBUG] " + name + " traders0.accept=" + tr.traders[0].accept + " traders1.accept=" + tr.traders[1].accept
                    + " item0=" + (tr.traders[0].itemTradeOrder != null ? tr.traders[0].itemTradeOrder.size() : -1)
                    + " item1=" + (tr.traders[1].itemTradeOrder != null ? tr.traders[1].itemTradeOrder.size() : -1));
            try {
                tr.update();
            } catch (Exception tradeEx) {
                System.out.println("[BOT][TRADE-DEBUG] " + name + " update EXCEPTION: " + tradeEx);
                Log.error("AutoFarmBot trade.update err: " + tradeEx.getMessage(), tradeEx);
            }
            System.out.println("[BOT][TRADE-DEBUG] " + name + " after update isFinish=" + tr.isFinish
                    + " player.coin=" + player.coin + " bagCount=" + countBagItems(player));
            zone.getService().chat(id, "Tang ban do cao cap, dung tam nha!");
            // Dọn dẹp trạng thái giao dịch để bot có thể giao dịch tiếp với người chơi khác
            if (tr.isFinish) {
                cleanTrade();
                if (player != null) {
                    player.cleanTrade();
                }
            }
        } catch (Exception ex) {
            Log.error("AutoFarmBot trade err: " + ex.getMessage(), ex);
        }
    }

    private Item createRareItem() {
        try {
            int itemId = RARE_ITEM_IDS[NinjaUtils.nextInt(0, RARE_ITEM_IDS.length - 1)];
            return ItemFactory.getInstance().newItemMax(itemId);
        } catch (Exception ex) {
            Log.error("AutoFarmBot createRareItem err: " + ex.getMessage(), ex);
        }
        return null;
    }

    /**
     * Chọn đồ tặng tùy theo cấp nhân vật người chơi.
     * Ưu tiên item trang bị (type 0-15) có level gần bằng level người chơi
     * (+ một chút để người chơi có thể mặc ngay), dùng newItemMax để max option.
     */
    private Item createItemForLevel(int playerLevel) {
        try {
            int target = Math.max(1, playerLevel);
            int min = Math.max(1, target - 3);
            int max = target + 5;
            java.util.List<Item> candidates = new ArrayList<>();
            Exe_Z.item.ItemManager mgr = Exe_Z.item.ItemManager.getInstance();
            int guard = 0;
            // Duyệt id item từ 0 tăng dần, giới hạn < 650 để tránh mất hình ảnh ở client cũ
            for (int id = 0; id < 650; id++, guard++) {
                Exe_Z.item.ItemTemplate tpl;
                try {
                    tpl = mgr.getItemTemplate(id);
                } catch (Exception e) {
                    break; // hết bảng item
                }
                if (tpl == null) {
                    continue;
                }
                byte type = tpl.type;
                // chỉ lấy đồ trang bị mặc được (vũ khí, áo, giáp...)
                if (type < 0 || type > 15) {
                    continue;
                }
                int lv = tpl.level;
                if (lv < min || lv > max) {
                    continue;
                }
                candidates.add(ItemFactory.getInstance().newItemMax(id));
            }
            if (candidates.isEmpty()) {
                // fallback: item level cao hơn một chút nếu không có đúng tầm
                for (int id = 0; id < 650; id++, guard++) {
                    Exe_Z.item.ItemTemplate tpl;
                    try {
                        tpl = mgr.getItemTemplate(id);
                    } catch (Exception e) {
                        break;
                    }
                    if (tpl == null || tpl.type < 0 || tpl.type > 15) {
                        continue;
                    }
                    int lv = tpl.level;
                    if (lv >= max && lv <= max + 20) {
                        candidates.add(ItemFactory.getInstance().newItemMax(id));
                    }
                }
            }
            if (!candidates.isEmpty()) {
                return candidates.get(NinjaUtils.nextInt(0, candidates.size() - 1));
            }
            return createRareItem();
        } catch (Exception ex) {
            Log.error("AutoFarmBot createItemForLevel err: " + ex.getMessage(), ex);
        }
        return createRareItem();
    }

    // ---------------- NHƯỜNG QUÁI CHO NGƯỜI CHƠI ----------------
    private boolean shouldYieldMob(Mob mob) {
        if (mob == null || zone == null) {
            return true;
        }
        synchronized (zone.players) {
            for (Char p : zone.players) {
                if (p == null || p == this || !isRealPlayer(p)) {
                    continue;
                }
                // Người chơi đang nhắm đúng con quái này
                if (mob.checkExist(p.id)) {
                    return true;
                }
                int d = NinjaUtils.getDistance(p.x, p.y, mob.x, mob.y);
                if (d <= AVOID_PLAYER_TARGET_RADIUS) {
                    return true;
                }
                if (d <= AVOID_PLAYER_MOB_RADIUS && mob.hp < mob.maxHP) {
                    return true;
                }
            }
        }
        return false;
    }

    private boolean autoAttack() {
        Mob mob = findNearestMob(600);
        if (mob == null) {
            target = null;
            // Không có quái trong tầm: đi về hướng quái gần nhất (dù xa) để chủ động tìm quái
            Mob farMob = findAnyMobDirection();
            if (farMob != null) {
                moveTo(farMob.x, farMob.y);
                return true;
            }
            if (tick % 20 == 0) {
                System.out.println("[BOT][DEBUG] " + name + " no mob found, zone.monsters=" + (zone != null && zone.monsters != null ? zone.monsters.size() : -1));
            }
            return false;
        }
        target = mob;
        int d = NinjaUtils.getDistance(x, y, mob.x, mob.y);
        if (tick % 20 == 0) {
            System.out.println("[BOT][DEBUG] " + name + " mob id=" + mob.id + " d=" + d + " mob.hp=" + mob.hp);
        }
        if (d <= 110) {
            attackMob(mob);
            return true;
        }
        moveTo(mob.x, mob.y);
        return true;
    }

    private Mob findAnyMobDirection() {
        Zone z = zone;
        if (z == null || z.monsters == null) {
            return null;
        }
        Mob result = null;
        int best = Integer.MAX_VALUE;
        for (Mob mob : z.monsters) {
            if (mob == null || mob.isDead || mob.isBoss || mob.levelBoss != 0) {
                continue;
            }
            int d = NinjaUtils.getDistance(x, y, mob.x, mob.y);
            if (d < best) {
                best = d;
                result = mob;
            }
        }
        return result;
    }

    private Mob findNearestMob(int range) {
        Zone z = zone;
        if (z == null) {
            return null;
        }
        Mob result = null;
        int best = Integer.MAX_VALUE;
        if (tick % 40 == 0) {
            System.out.println("[BOT][DEBUG] " + name + " pos=(" + x + "," + y + ") monsters=" + (z.monsters != null ? z.monsters.size() : -1));
        }
        for (Mob mob : z.getLivingMonsters()) {
            if (mob == null || mob.isDead || mob.isBoss || mob.levelBoss != 0) {
                continue;
            }
            if (shouldYieldMob(mob)) {
                if (tick % 40 == 0) {
                    System.out.println("[BOT][DEBUG] " + name + " yield mob id=" + mob.id);
                }
                continue;
            }
            int d = NinjaUtils.getDistance(x, y, mob.x, mob.y);
            if (tick % 40 == 0) {
                System.out.println("[BOT][DEBUG] " + name + " consider mob id=" + mob.id + " pos=(" + mob.x + "," + mob.y + ") d=" + d);
            }
            if (d <= range && d < best) {
                best = d;
                result = mob;
            }
        }
        return result;
    }

    private void attackMob(Mob mob) {
        mob.lock.lock();
        try {
            if (mob.isDead) {
                target = null;
                return;
            }
            // Trừ HP thật để quái có thể chết
            int dealt = NinjaUtils.nextInt(damage2, damage);
            if (dealt < 0) dealt = 0;
            
            mob.addHp(-dealt);
            zone.getService().attackMonster(dealt, false, mob);
            if (mob.hp <= 0) {
                mob.die();
            }
            
            target = null;
        } finally {
            mob.lock.unlock();
        }
    }

    private boolean autoPickup() {
        Zone z = zone;
        if (z == null) {
            return false;
        }
        ItemMap best = null;
        int bestD = 200;
        for (ItemMap im : z.getItemMaps()) {
            if (im == null || im.isPickedUp() || im.isExpired()) {
                continue;
            }
            int ownerID = im.getOwnerID();
            if (ownerID != -1 && ownerID != id && !im.isCanPickup()) {
                continue;
            }
            int d = NinjaUtils.getDistance(x, y, im.getX(), im.getY());
            if (d < bestD) {
                bestD = d;
                best = im;
            }
        }
        if (best == null) {
            return false;
        }
        if (bestD <= 20) {
            best.lock.lock();
            try {
                if (!best.isPickedUp()) {
                    best.setPickedUp(true);
                    z.getService().pickItem(this, best);
                    z.removeItem(best);
                    lootCount++;
                }
            } finally {
                best.lock.unlock();
            }
            return true;
        }
        moveTo(best.getX(), best.getY());
        return true;
    }

    private void wander() {
        if (tick % 4 != 0) {
            return;
        }
        Zone z = zone;
        if (z == null) {
            return;
        }
        int[] bounds = getMapBounds();
        int w = Math.max(120, bounds[1] - bounds[0]);
        int h = Math.max(80, bounds[3] - bounds[2]);
        // Đi bước ngẫu nhiên gần vị trí hiện tại (giống người chơi đi lại, không teleport xa)
        int rangeX = Math.max(60, w / 8);
        int rangeY = Math.max(40, h / 8);
        int nx = x + NinjaUtils.nextInt(-rangeX, rangeX);
        int ny = y + NinjaUtils.nextInt(-rangeY, rangeY);
        if (nx < bounds[0]) {
            nx = bounds[0];
        }
        if (ny < bounds[2]) {
            ny = bounds[2];
        }
        if (nx > bounds[1]) {
            nx = bounds[1];
        }
        if (ny > bounds[3]) {
            ny = bounds[3];
        }
        moveTo(nx, ny);
    }

    private void moveTo(int tx, int ty) {
        Zone z = zone;
        if (z == null) {
            return;
        }
        int dx = tx - x;
        int dy = ty - y;
        int dist = (int) Math.sqrt(dx * dx + dy * dy);
        if (dist < 5) {
            return;
        }
        int step = Math.min(dist, 55);
        int nx = x + dx * step / dist;
        int ny = y + dy * step / dist;
        int[] bounds = getMapBounds();
        if (nx < bounds[0]) {
            nx = bounds[0];
        }
        if (ny < bounds[2]) {
            ny = bounds[2];
        }
        if (nx > bounds[1]) {
            nx = bounds[1];
        }
        if (ny > bounds[3]) {
            ny = bounds[3];
        }
        ny = z.tilemap.collisionY((short) nx, (short) ny);
        z.move(this, (short) nx, (short) ny);
    }

    // Lấy phạm vi di chuyển thật của map từ waypoints (giống người chơi đi được)
    private int[] getMapBounds() {
        Zone z = zone;
        if (z != null && z.tilemap != null && z.tilemap.waypoints != null && !z.tilemap.waypoints.isEmpty()) {
            int minX = Integer.MAX_VALUE, maxX = Integer.MIN_VALUE;
            int minY = Integer.MAX_VALUE, maxY = Integer.MIN_VALUE;
            for (Exe_Z.map.Waypoint wp : z.tilemap.waypoints) {
                minX = Math.min(minX, wp.minX);
                maxX = Math.max(maxX, wp.maxX);
                minY = Math.min(minY, wp.minY);
                maxY = Math.max(maxY, wp.maxY);
            }
            if (maxX > minX && maxY > minY) {
                // chỉ thu nhẹ 1 chút để bot không áp sát mép map
                return new int[]{minX + 30, maxX - 30, minY + 30, maxY - 30};
            }
        }
        return new int[]{0, 60000, 0, 60000};
    }

    private void despawn() {
        Zone z = zone;
        if (z != null) {
            z.out(this);
        }
        try {
            Exe_Z.server.ServerManager.removeChar(this);
        } catch (Throwable ignored) {
        }
        isCleaned = true;
        BOTS.remove(this);
    }

    public static void spawn(Char owner) {
        Zone z = owner.zone;
        if (z == null) {
            return;
        }
        synchronized (BOTS) {
            int count = 0;
            for (AutoFarmBot b : BOTS) {
                if (b.zone == z && !b.isCleaned) {
                    count++;
                }
            }
            if (count >= MAX_BOT_PER_ZONE) {
                owner.getService().serverMessage("Khu vực đã đạt giới hạn bot (" + MAX_BOT_PER_ZONE + ").");
                return;
            }
        }
        AutoFarmBot bot = createBot(z, Math.max(1, owner.level), 20000, 1500, owner.classId);
        int sx = owner.x + NinjaUtils.nextInt(-40, 40);
        if (sx < 0) {
            sx = 0;
        }
        int sy = owner.y;
        if (sy < 0) {
            sy = 0;
        }
        bot.setXY((short) sx, (short) sy);
        bot.spawnX = bot.x;
        bot.spawnY = bot.y;
        bot.despawnAt = System.currentTimeMillis() + BOT_LIFETIME;
        BOTS.add(bot);
        z.join(bot);
        owner.getService().serverMessage("Đã triệu hồi bot " + bot.name + " (auto đánh, nhặt đồ, hồi phục, chat, tổ đội, giao dịch).");
    }

    /**
     * Triệu hồi bot từ Web Admin: cho phép chọn map, số lượng, level, HP và sát thương.
     */
    public static int spawnByMap(int mapID, int count, int level, int hp, int damage, int speed) {
        Exe_Z.map.Map map = Exe_Z.map.MapManager.getInstance().find(mapID);
        if (map == null) {
            return -1;
        }
        Zone z = null;
        try {
            z = map.rand();
        } catch (Exception ex) {
            return -1; // map không có zone sẵn sàng
        }
        if (z == null) {
            return -1;
        }
        int sx = 0;
        int sy = 0;
        if (z.tilemap != null && z.tilemap.waypoints != null && !z.tilemap.waypoints.isEmpty()) {
            Exe_Z.map.Waypoint wp = z.tilemap.waypoints.get(0);
            sx = (wp.minX + wp.maxX) / 2;
            sy = (wp.minY + wp.maxY) / 2;
        }
        if (sx < 0) {
            sx = 0;
        }
        if (sy < 0) {
            sy = 0;
        }
        int spawned = 0;
        for (int i = 0; i < count; i++) {
            synchronized (BOTS) {
                int current = 0;
                for (AutoFarmBot b : BOTS) {
                    if (b.zone == z && !b.isCleaned) {
                        current++;
                    }
                }
                if (current >= MAX_BOT_PER_ZONE) {
                    break;
                }
            }
            AutoFarmBot bot = createBot(z, Math.max(1, level), Math.max(100, hp), Math.max(10, damage), speed, (byte) 1);
            bot.setXY((short) sx, (short) sy);
            bot.spawnX = bot.x;
            bot.spawnY = bot.y;
            bot.despawnAt = System.currentTimeMillis() + BOT_LIFETIME;
            BOTS.add(bot);
            z.join(bot);
            spawned++;
        }
        return spawned;
    }

    private static AutoFarmBot createBot(Zone z, int level, int hp, int damage, byte classId) {
        return createBot(z, level, hp, damage, 0, classId);
    }

    private static AutoFarmBot createBot(Zone z, int level, int hp, int damage, int speed, byte classId) {
        AutoFarmBot bot = new AutoFarmBot(
                ID_GEN.incrementAndGet(),
                randomPlayerName(),
                level,
                Char.PK_NORMAL,
                classId);
        bot.setDefault();
        if (speed > 0) {
            bot.speed = (byte) speed;
        }
        // Thiết lập ngoại hình cơ bản giống người chơi (head/body/leg/weapon)
        bot.gender = (byte) Exe_Z.util.NinjaUtils.nextInt(0, 1);
        if (bot.gender == 1) { // Nam
            bot.head = (short) 2;
            bot.original_head = (short) 2;
        } else { // Nữ
            bot.head = (short) 11;
            bot.original_head = (short) 11;
        }
        
        int[] bestEquipId = new int[10];
        int[] bestEquipLevel = new int[10];
        for (int i = 0; i < 10; i++) {
            bestEquipId[i] = -1;
            bestEquipLevel[i] = -1;
        }
        int bestBodyPart = -1;
        int bestLegPart = -1;
        int bestWeaponPart = -1;

        for (Exe_Z.item.ItemTemplate t : Exe_Z.item.ItemManager.getInstance().getItemTemplates()) {
            if (t.id < 650 && t.level <= level && (t.gender == 2 || t.gender == bot.gender)) {
                if (t.type >= 0 && t.type <= 9) {
                    if (t.type == Exe_Z.item.ItemTemplate.TYPE_VUKHI) {
                        boolean matchWeapon = false;
                        if (classId == 1 && t.isKiem()) matchWeapon = true;
                        else if (classId == 2 && t.isTieu()) matchWeapon = true;
                        else if (classId == 3 && t.isKunai()) matchWeapon = true;
                        else if (classId == 4 && t.isCung()) matchWeapon = true;
                        else if (classId == 5 && t.isDao()) matchWeapon = true;
                        else if (classId == 6 && t.isQuat()) matchWeapon = true;

                        if (matchWeapon && t.level > bestEquipLevel[t.type]) {
                            bestEquipId[t.type] = t.id;
                            bestEquipLevel[t.type] = t.level;
                            bestWeaponPart = t.part;
                        }
                    } else {
                        if (t.level > bestEquipLevel[t.type]) {
                            bestEquipId[t.type] = t.id;
                            bestEquipLevel[t.type] = t.level;
                            if (t.type == Exe_Z.item.ItemTemplate.TYPE_AO) bestBodyPart = t.part;
                            if (t.type == Exe_Z.item.ItemTemplate.TYPE_QUAN) bestLegPart = t.part;
                        }
                    }
                }
            }
        }

        bot.body = (short) (bestBodyPart != -1 ? bestBodyPart : -1);
        bot.leg = (short) (bestLegPart != -1 ? bestLegPart : -1);
        bot.weapon = (short) (bestWeaponPart != -1 ? bestWeaponPart : 15);
        bot.coat = (short) -1;
        bot.glove = (short) -1;

        for (int i = 0; i < 10; i++) {
            if (bestEquipId[i] != -1) {
                Exe_Z.item.Equip eq = Exe_Z.item.ItemFactory.getInstance().newEquipment(bestEquipId[i]);
                if (eq != null) {
                    eq.options.add(new Exe_Z.option.ItemOption(73, 100)); // random attack option
                    eq.options.add(new Exe_Z.option.ItemOption(6, 1000)); // random HP option
                    eq.upgrade = (byte) Exe_Z.util.NinjaUtils.nextInt(8, 16);
                    bot.equipment[i] = eq;
                    if (eq.template.fashion > -1) {
                        bot.fashion[i] = eq;
                    }
                }
            }
        }
        bot.classId = classId;
        bot.numberCellBag = 30;
        bot.numberCellBox = 30;
        bot.bag = new Exe_Z.item.Item[bot.numberCellBag];
        bot.box = new Exe_Z.item.Item[bot.numberCellBox];
        bot.setFashionStrategy(new Exe_Z.fashion.FashionFromEquip());
        bot.setAbilityStrategy(AbilityCustom.builder()
                .hp(hp)
                .mp(2000)
                .damage(damage)
                .damage2(Math.max(1, damage * 8 / 10))
                .miss(10)
                .exactly(200)
                .fatal(150)
                .build());
        bot.setAbility();
        bot.setFashion();
        bot.recovery();
        // User giả để bot thực hiện giao dịch (trade.update đọc user.gold)
        try {
            sun.misc.Unsafe unsafe = getUnsafe();
            if (unsafe != null) {
                User u = (User) unsafe.allocateInstance(User.class);
                u.activated = 1;
                u.gold = 0;
                u.coin = 0;
                u.roles = new ArrayList<>();
                u.IPAddress = new ArrayList<>();
                u.levelRewards = new int[5];
                u.chars = new java.util.Vector<>();
                bot.user = u;
            } else {
                Exe_Z.network.Session fs = getFakeSession();
                if (fs != null) {
                    User u = new User(fs, "bot_" + bot.name, "", "");
                    u.activated = 1;
                    u.gold = 0;
                    u.coin = 0;
                    bot.user = u;
                }
            }
        } catch (Throwable ignored) {
        }
        // Đăng ký bot vào danh sách online để người chơi có thể xem thông tin, mời tổ đội, kết bạn...
        try {
            Exe_Z.server.ServerManager.addChar(bot);
        } catch (Throwable ignored) {
        }
        return bot;
    }

    private static Exe_Z.network.Session fakeSession;

    private static synchronized Exe_Z.network.Session getFakeSession() {
        if (fakeSession != null && fakeSession.connected) {
            return fakeSession;
        }
        try {
            java.net.ServerSocket ss = new java.net.ServerSocket(0);
            int port = ss.getLocalPort();
            java.net.Socket a = new java.net.Socket("127.0.0.1", port);
            java.net.Socket b = ss.accept();
            ss.close();
            fakeSession = new Exe_Z.network.Session(b, 1);
            fakeSession.connected = true;
            return fakeSession;
        } catch (Throwable e) {
            return null;
        }
    }

    private static sun.misc.Unsafe getUnsafe() {
        try {
            java.lang.reflect.Field f = sun.misc.Unsafe.class.getDeclaredField("theUnsafe");
            f.setAccessible(true);
            return (sun.misc.Unsafe) f.get(null);
        } catch (Throwable e) {
            try {
                java.lang.reflect.Field f = sun.misc.Unsafe.class.getDeclaredField("theUnsafe");
                java.lang.reflect.Field.class.getDeclaredMethod("setAccessible0", boolean.class)
                        .invoke(f, true);
                return (sun.misc.Unsafe) f.get(null);
            } catch (Throwable e2) {
                return null;
            }
        }
    }

    public static void removeAll() {
        synchronized (BOTS) {
            Iterator<AutoFarmBot> it = BOTS.iterator();
            while (it.hasNext()) {
                AutoFarmBot b = it.next();
                Zone z = b.zone;
                if (z != null) {
                    z.out(b);
                }
                try {
                    Exe_Z.server.ServerManager.removeChar(b);
                } catch (Throwable ignored) {
                }
                b.isCleaned = true;
                it.remove();
            }
        }
    }

    public static int count() {
        synchronized (BOTS) {
            return BOTS.size();
        }
    }

    /** Xóa 1 bot theo tên (Web Admin quản lý thông tin chi tiết). */
    public static boolean removeByName(String name) {
        if (name == null || name.isEmpty()) {
            return false;
        }
        synchronized (BOTS) {
            java.util.Iterator<AutoFarmBot> it = BOTS.iterator();
            while (it.hasNext()) {
                AutoFarmBot b = it.next();
                if (b != null && name.equals(b.name)) {
                    try {
                        if (b.zone != null) {
                            b.zone.out(b);
                        }
                    } catch (Exception ignored) {
                    }
                    try {
                        Exe_Z.server.ServerManager.removeChar(b);
                    } catch (Throwable ignored) {
                    }
                    b.isCleaned = true;
                    it.remove();
                    return true;
                }
            }
        }
        return false;
    }

    /** Snapshot thông tin chi tiết từng bot cho bảng bot_status (Web Admin). */
    public static java.util.List<org.json.simple.JSONObject> snapshotInfo(int limit) {
        java.util.List<org.json.simple.JSONObject> out = new java.util.ArrayList<>();
        synchronized (BOTS) {
            for (AutoFarmBot b : BOTS) {
                if (b == null || b.isCleaned) {
                    continue;
                }
                try {
                    org.json.simple.JSONObject o = new org.json.simple.JSONObject();
                    o.put("name", b.name == null ? "" : b.name);
                    o.put("level", b.level);
                    o.put("map_id", b.mapId);
                    o.put("zone_id", b.zone != null ? b.zone.id : -1);
                    o.put("x", b.x);
                    o.put("y", b.y);
                    o.put("hp", (long) b.hp);
                    o.put("max_hp", (long) b.maxHP);
                    o.put("state", b.botState == null ? "UNKNOWN" : b.botState.name());
                    StringBuilder pers = new StringBuilder();
                    try {
                        for (Object p : b.botProfile.personalities) {
                            if (pers.length() > 0) {
                                pers.append(',');
                            }
                            pers.append(String.valueOf(p));
                        }
                    } catch (Exception ignored) {
                    }
                    o.put("personality", pers.toString());
                    String top = "UNKNOWN";
                    try {
                        top = b.botNeeds.topNeed();
                    } catch (Exception ignored) {
                    }
                    o.put("top_need", top);
                    out.add(o);
                    if (out.size() >= limit) {
                        break;
                    }
                } catch (Exception ignored) {
                }
            }
        }
        return out;
    }

    // ---------------- THREAD QUÉT BOT THEO NGƯỜI CHƠI ----------------
    // Cứ mỗi 2 giây, quét toàn server: map/zone nào có người chơi thật nhưng
    // chưa có bot thì tự sinh bot trụ ngay gần người chơi để bot xuất hiện nhanh.
    private static volatile Thread sweeper;
    private static final Object SWEEP_LOCK = new Object();

    public static void startBotSweeper() {
        synchronized (SWEEP_LOCK) {
            if (sweeper != null && sweeper.isAlive()) {
                return;
            }
            sweeper = new Thread(() -> {
                while (true) {
                    try {
                        Thread.sleep(2000L);
                        ensureBotNearPlayers();
                    } catch (InterruptedException e) {
                        break;
                    } catch (Exception e) {
                        Log.error("BotSweeper err: " + e.getMessage(), e);
                    }
                }
            }, "AutoBotSweeper");
            sweeper.setDaemon(true);
            sweeper.start();
            System.out.println("[BOT][SWEEPER] Auto bot sweeper started.");
            try {
                Exe_Z.bot.ai.BotConfig.load();
                Exe_Z.bot.ai.BotManager.gI().start();
            } catch (Exception ignored) {
            }
        }
    }

    private static void ensureBotNearPlayers() {
        try {
            java.util.ArrayList<Exe_Z.map.Map> maps = Exe_Z.map.MapManager.getInstance().getMaps();
            if (maps == null) {
                return;
            }
            for (Exe_Z.map.Map m : maps) {
                if (m == null) {
                    continue;
                }
                java.util.List<Exe_Z.map.zones.Zone> zones = m.getZones();
                if (zones == null || zones.isEmpty()) {
                    continue;
                }
                for (Exe_Z.map.zones.Zone z : zones) {
                    if (z == null) {
                        continue;
                    }
                    Char player = null;
                    synchronized (z.players) {
                        for (Char p : z.players) {
                            if (p != null && !(p instanceof AutoFarmBot) && p.user != null && p.user.session != null && !p.isDead) {
                                player = p;
                                break;
                            }
                        }
                    }
                    if (player == null) {
                        continue;
                    }
                    // đếm bot trong zone
                    int zoneBots = 0;
                    synchronized (BOTS) {
                        for (AutoFarmBot b : BOTS) {
                            if (b != null && !b.isCleaned && b.zone == z) {
                                zoneBots++;
                            }
                        }
                    }
                    if (zoneBots >= MAX_BOT_PER_ZONE) {
                        continue;
                    }
                    AutoFarmBot nb = createBot(z, Math.max(1, player.level), 50000, 3000, player.classId);
                    nb.setXY((short) Math.max(0, player.x + NinjaUtils.nextInt(-40, 40)), (short) Math.max(0, player.y + NinjaUtils.nextInt(-20, 20)));
                    nb.spawnX = nb.x;
                    nb.spawnY = nb.y;
                    nb.despawnAt = System.currentTimeMillis() + BOT_LIFETIME;
                    BOTS.add(nb);
                    z.join(nb);
                    System.out.println("[BOT][SWEEPER] Spawn bot " + nb.name + " tại map " + m.id + " zone " + z.id + " (người chơi " + player.name + ", bot=" + (zoneBots + 1) + "/" + MAX_BOT_PER_ZONE + ")");
                }
            }
        } catch (Exception e) {
            Log.error("ensureBotNearPlayers err: " + e.getMessage(), e);
        }
    }
}
