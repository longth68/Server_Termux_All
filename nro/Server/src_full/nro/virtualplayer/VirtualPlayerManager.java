package nro.virtualplayer;

import Utils.Logger;
import nro.player.Player;
import nro.services.Fun.ChangeMapService;
import nro.virtualplayer.core.VirtualProfile;
import nro.virtualplayer.core.VirtualState;
import java.util.ArrayList;
import java.util.List;
import java.util.Random;
import java.util.concurrent.ConcurrentHashMap;
import java.util.concurrent.ConcurrentMap;

/**
 * Quản lý toàn bộ Virtual Player.
 * PHASE 2 - Virtual Player Core.
 * create/remove/start/stop, lifecycle, population, scheduler, persistence.
 */
public class VirtualPlayerManager implements Runnable {

    private static VirtualPlayerManager instance;

    private final ConcurrentMap<Long, VirtualPlayer> players = new ConcurrentHashMap<>();
    private final Random random = new Random();
    // Tên bot đã inject vào bảng xếp hạng (để xoá khi refresh)
    private final java.util.Set<String> rankBotNames = java.util.Collections.synchronizedSet(new java.util.HashSet<>());

    // Cấu hình population
    private int targetPopulation = 50;
    private boolean systemEnabled = false;

    private long nextIdBase = 3_000_000_000L; // ID VP trong dải riêng (tránh đụng player thật)

    public static VirtualPlayerManager gI() {
        if (instance == null) {
            instance = new VirtualPlayerManager();
        }
        return instance;
    }

    /**
     * Khởi tạo hệ thống khi thread chạy: đọc config + tạo dân số ban đầu.
     * Config file: virtualplayer_config.txt (enabled=, population=)
     */
    public void init() {
        if (initialized) return;
        initialized = true;
        VirtualConfig cfg = VirtualConfig.gI();
        this.systemEnabled = cfg.enabled;
        this.targetPopulation = cfg.population;
        Logger.system("VirtualPlayer", "Config: enabled=" + systemEnabled + " | population=" + targetPopulation
                + " | expRate=" + cfg.expRate + " | goldRate=" + cfg.goldRate + " | playerProtection=" + cfg.playerProtection);
        Logger.system("VirtualPlayer", "Presence: giữ " + cfg.presencePerPlayer
                + " bot/người chơi | luân phiên mỗi " + cfg.presenceVisitSeconds + "s (0 = tắt, sống hoàn toàn độc lập)");
        if (systemEnabled) {
            restoreSaved();
            fillToTarget();
            injectRankings();
        }
    }

    /**
     * Đưa bot vào bảng xếp hạng Sức mạnh (TOP_SUC_MANH).
     * QUY TẮC: bot KHÔNG BAO GIỜ vượt player thật — luôn nằm dưới mọi entry thật,
     * power bị cap = min(botPower, minRealPower - 1).
     * Thay THAM CHIẾU list mới (giống cách DB reload làm) -> an toàn thread.
     */
    public void injectRankings() {
        try {
            List<nro.top.TOP> cur = nro.server.TopServer.TOP_SUC_MANH;
            if (cur == null || players.isEmpty()) return;

            // List mới: bỏ entry bot cũ, giữ nguyên player thật
            List<nro.top.TOP> fresh = new ArrayList<>(cur.size() + 8);
            for (nro.top.TOP t : cur) {
                if (!rankBotNames.contains(t.getName())) {
                    fresh.add(t);
                }
            }

            // Power thấp nhất của player thật trong bảng
            long minReal = Long.MAX_VALUE;
            for (nro.top.TOP t : fresh) {
                if (t.getPower() < minReal) minReal = t.getPower();
            }

            // Bot sắp theo power giảm dần, append cuối bảng
            List<VirtualPlayer> bots = new ArrayList<>();
            for (VirtualPlayer vp : getBots()) {
                if (vp.nPoint != null) bots.add(vp);
            }
            bots.sort((a, b) -> Long.compare(b.nPoint.power, a.nPoint.power));

            int rank = fresh.size();
            for (VirtualPlayer vp : bots) {
                if (fresh.size() >= 120) break; // giới hạn tổng entry bảng xếp hạng
                long p = Math.min(vp.nPoint.power, Math.max(0, minReal - 1));
                rank++;
                nro.top.TOP t = nro.top.TOP.builder()
                        .name(vp.name)
                        .gender(vp.gender)
                        .head(vp.head)
                        .body(vp.body_)
                        .leg(vp.leg_)
                        .power(p)
                        .top(rank)
                        .build();
                fresh.add(t);
                rankBotNames.add(vp.name);
            }
            nro.server.TopServer.TOP_SUC_MANH = fresh;
        } catch (Exception e) {
            Logger.logException(VirtualPlayerManager.class, e);
        }
    }

    /**
     * Khôi phục bot đã lưu từ lần chạy trước (không reset tiến trình).
     */
    private void restoreSaved() {
        try {
            org.json.simple.JSONArray saved = VirtualPersistence.loadAll();
            if (saved == null) return;
            for (Object o : saved) {
                try {
                    org.json.simple.JSONObject j = (org.json.simple.JSONObject) o;
                    VirtualPlayer vp = createBotFromSave(j);
                    if (vp != null) {
                        players.put(vp.id, vp);
                    }
                } catch (Exception ignored) {}
            }
            if (!saved.isEmpty()) {
                Logger.system("VirtualPlayer", "Đã khôi phục " + saved.size() + " bot từ dữ liệu lưu");
            }
        } catch (Exception e) {
            Logger.logException(VirtualPlayerManager.class, e);
        }
    }

    /**
     * Tạo VirtualPlayer từ dữ liệu JSON đã lưu.
     */
    private VirtualPlayer createBotFromSave(org.json.simple.JSONObject j) {
        String name = (String) j.get("name");
        if (name == null) return null;
        byte gender = toByte(j.get("gender"), (byte) 0);
        short head = toShort(j.get("head"), (short) 1);
        short body = toShort(j.get("body"), (short) 1);
        short leg = toShort(j.get("leg"), (short) 1);

        VirtualPlayer vp = new VirtualPlayer(nextIdBase++, name, gender, head, body, leg, (short) 0, (byte) 0);

        // Stats
        if (vp.nPoint != null) {
            Long power = toLong(j.get("power"), null);
            if (power != null) {
                vp.nPoint.power = power;
                vp.nPoint.tiemNang = power;
                vp.nPoint.limitPower = nro.player.NPoint.MAX_LIMIT;
            }
            Integer hpg = toInteger(j.get("hpg"), null);
            if (hpg != null) {
                vp.nPoint.hpg = hpg;
                vp.nPoint.hpMax = hpg;
                vp.nPoint.hp = hpg;
            }
            Integer dameg = toInteger(j.get("dameg"), null);
            if (dameg != null) {
                vp.nPoint.dameg = dameg;
                vp.nPoint.dame = dameg;
            }
            Integer defg = toInteger(j.get("defg"), null);
            if (defg != null) {
                vp.nPoint.defg = defg;
                vp.nPoint.def = defg;
            }
            vp.nPoint.mpMax = vp.nPoint.hpg / 2;
            vp.nPoint.mp = vp.nPoint.mpMax;
            vp.nPoint.mpg = vp.nPoint.hpg / 2;
        }
        if (vp.inventory != null) {
            Long gold = toLong(j.get("gold"), null);
            if (gold != null) {
                vp.inventory.gold = gold;
            }
        }

        // Khôi phục personality + trait từ save
        try {
            Object persArr = j.get("personalities");
            if (persArr instanceof org.json.simple.JSONArray) {
                java.util.List<String> names = new java.util.ArrayList<>();
                for (Object p : (org.json.simple.JSONArray) persArr) {
                    if (p != null) names.add(p.toString());
                }
                vp.profile.restoreFromSave(names,
                        toFloat(j.get("talkativeness"), null),
                        toFloat(j.get("riskTolerance"), null),
                        toFloat(j.get("helpfulness"), null),
                        toFloat(j.get("competitiveness"), null),
                        toFloat(j.get("laziness"), null),
                        toFloat(j.get("greed"), null));
            }
        } catch (Exception e) {
            Logger.logException(VirtualPlayerManager.class, e);
        }

        // Khôi phục quan hệ (friend/rival) từ save
        try {
            Object rels = j.get("relations");
            if (rels instanceof org.json.simple.JSONObject) {
                for (Object e : ((org.json.simple.JSONObject) rels).entrySet()) {
                    java.util.Map.Entry en = (java.util.Map.Entry) e;
                    if (en.getKey() != null && en.getValue() instanceof Number) {
                        vp.memory.adjustRelation(en.getKey().toString(), ((Number) en.getValue()).floatValue());
                    }
                }
            }
        } catch (Exception ignored) {}

        // Trang bị theo power đã khôi phục
        try {
            vp.getEquipment().giveStarterGear();
        } catch (Exception e) {
            Logger.logException(VirtualPlayerManager.class, e);
        }
        // Giao nhiệm vụ nếu chưa có
        try {
            if (vp.playerTask != null && vp.playerTask.taskMain == null) {
                vp.playerTask.taskMain = nro.services.TaskService.gI().getTaskMainById(vp, 1);
            }
        } catch (Exception e) {}

        // Vào lại map cũ đã lưu — spawn ĐỘC LẬP (không ép bám player).
        // Hệ hiện diện luân phiên (maintainPresence) sẽ tự kéo bot tới khu player khi cần.
        int lastMap = toInteger(j.get("lastMapId"), -1);
        if (lastMap >= 0) {
            try { vp.joinMap(lastMap, 100 + random.nextInt(200), 384); } catch (Exception ignored) {}
        } else {
            try { vp.joinMap(39 + vp.gender, 100 + random.nextInt(200), 384); } catch (Exception ignored) {}
        }
        return vp;
    }

    /**
     * Tìm zone đang có player thật (ngẫu nhiên trong các zone còn chỗ trống).
     * @return zone chứa player thật, hoặc null nếu không ai online / lỗi
     */
    private nro.map.Zone findRealPlayerZone() {
        try {
            List<nro.map.Zone> candidates = new ArrayList<>();
            for (Player pl : nro.server.Client.gI().getPlayersSnapshot()) {
                if (pl == null || pl.isBot || pl.isBoss || pl.isDeTu || !pl.isPlayer) continue;
                if (pl.zone == null || pl.zone.map == null) continue;
                if (!candidates.contains(pl.zone)) candidates.add(pl.zone);
            }
            if (candidates.isEmpty()) return null;
            // Ưu tiên các khu chưa đầy
            List<nro.map.Zone> free = new ArrayList<>();
            for (nro.map.Zone z : candidates) {
                try {
                    if (!z.isFullPlayer()) free.add(z);
                } catch (Exception ignored) {}
            }
            if (!free.isEmpty()) candidates = free;
            return candidates.get(random.nextInt(candidates.size()));
        } catch (Exception e) {
            return null;
        }
    }

    /**
     * Đưa bot vào CÙNG KHU với một player thật ngẫu nhiên.
     * @return true nếu join thành công; false -> caller fallback về map town
     */
    public boolean joinNearRealPlayers(VirtualPlayer vp) {
        try {
            nro.map.Zone z = findRealPlayerZone();
            if (z == null) return false;
            int x = 100 + random.nextInt(200);
            return vp.joinZone(z, x, 384);
        } catch (Exception e) {
            return false;
        }
    }

    private static byte toByte(Object o, byte def) {
        try { return o instanceof Number ? ((Number) o).byteValue() : def; } catch (Exception e) { return def; }
    }
    private static short toShort(Object o, short def) {
        try { return o instanceof Number ? ((Number) o).shortValue() : def; } catch (Exception e) { return def; }
    }
    private static Long toLong(Object o, Long def) {
        try { return o instanceof Number ? ((Number) o).longValue() : def; } catch (Exception e) { return def; }
    }
    private static Float toFloat(Object o, Float def) {
        try { return o instanceof Number ? ((Number) o).floatValue() : def; } catch (Exception e) { return def; }
    }
    private static Integer toInteger(Object o, Integer def) {
        try { return o instanceof Number ? ((Number) o).intValue() : def; } catch (Exception e) { return def; }
    }

    private boolean initialized = false;

    private void loadConfig() {
        VirtualConfig cfg = VirtualConfig.gI();
        this.systemEnabled = cfg.enabled;
        this.targetPopulation = cfg.population;
    }

    // ===== LIFE CYCLE =====
    public VirtualPlayer createBot() {
        String name = VirtualProfile.randomName();
        byte gender = (byte) random.nextInt(3);
        short head = (short) (1 + random.nextInt(90));
        short body = (short) (1 + random.nextInt(40));
        short leg = (short) (1 + random.nextInt(40));

        VirtualPlayer vp = new VirtualPlayer(nextIdBase++, name, gender, head, body, leg, (short) 0, (byte) 0);
        try {
            vp.getEquipment().giveStarterGear();
        } catch (Exception e) {
            Logger.logException(VirtualPlayerManager.class, e);
        }
        // Giao nhiệm vụ chính đầu tiên (task ID 1 — bỏ qua tutorial)
        try {
            if (vp.playerTask != null && vp.playerTask.taskMain == null) {
                vp.playerTask.taskMain = nro.services.TaskService.gI().getTaskMainById(vp, 1);
            }
        } catch (Exception e) {}
        // Spawn ĐỘC LẬP: về làng theo giới tính; Brain sẽ tự tản ra map theo sức mạnh.
        // (Không còn ép spawn cạnh player — hiện diện gần player do maintainPresence lo, luân phiên.)
        vp.joinMap(39 + gender, 100 + random.nextInt(200), 384);
        players.put(vp.id, vp);
        return vp;
    }

    public boolean removeBot(long id) {
        VirtualPlayer vp = players.remove(id);
        if (vp != null) {
            try {
                ChangeMapService.gI().exitMap(vp);
            } catch (Exception ignored) {}
            return true;
        }
        return false;
    }

    public void removeAllBots() {
        List<Long> ids = new ArrayList<>(players.keySet());
        for (Long id : ids) {
            removeBot(id);
        }
    }

    public VirtualPlayer getBot(long id) {
        return players.get(id);
    }

    /**
     * Tra bot theo id int (do ConsignItem.player_sell la int, bot id 3.000.000.000+ bi cat bot).
     */
    public VirtualPlayer getBotByIntId(int intId) {
        for (VirtualPlayer vp : players.values()) {
            if (vp != null && (int) vp.id == intId) return vp;
        }
        return null;
    }

    public List<VirtualPlayer> getBots() {
        return new ArrayList<>(players.values());
    }

    public List<VirtualPlayer> getOnlineBots() {
        List<VirtualPlayer> list = new ArrayList<>();
        for (VirtualPlayer vp : players.values()) {
            if (vp.isOnline()) list.add(vp);
        }
        return list;
    }

    public int count() {
        return players.size();
    }

    // ===== ENABLE / POPULATION =====
    public void setSystemEnabled(boolean on) {
        this.systemEnabled = on;
        if (on) {
            fillToTarget();
        } else {
            removeAllBots();
        }
        Logger.success("[VirtualPlayer] System " + (on ? "ENABLED" : "DISABLED") + "\n");
    }

    public boolean isSystemEnabled() {
        return systemEnabled;
    }

    public void setTargetPopulation(int n) {
        this.targetPopulation = Math.max(0, n);
        fillToTarget();
    }

    public int getTargetPopulation() {
        return targetPopulation;
    }

    private void fillToTarget() {
        if (!systemEnabled) return;
        while (players.size() < targetPopulation) {
            try {
                createBot();
            } catch (Exception e) {
                Logger.logException(VirtualPlayerManager.class, e);
                break;
            }
        }
    }

    // ===== MAIN LOOP =====
    @Override
    public void run() {
        init();
        long lastSave = System.currentTimeMillis();
        long lastProgression = System.currentTimeMillis();
        while (true) {
            try {
                Thread.sleep(1000);
                if (!systemEnabled) continue;

                // Top-up population nếu thiếu
                fillToTarget();

                // Tick từng VP
                for (VirtualPlayer vp : players.values()) {
                    try {
                        vp.tick();
                    } catch (Exception e) {
                        Logger.logException(VirtualPlayerManager.class, e);
                    }
                }

                // Hiện diện luân phiên gần người chơi thật (mỗi 15s)
                if (System.currentTimeMillis() - lastPresenceCheck > 15_000) {
                    lastPresenceCheck = System.currentTimeMillis();
                    maintainPresence();
                }

                // World simulation: bot tiến triển CHẬM hơn player (mỗi 60s)
                if (System.currentTimeMillis() - lastProgression > 60_000) {
                    lastProgression = System.currentTimeMillis();
                    simulateProgression();
                    // Refresh bảng xếp hạng (TOP_SUC_MANH bị DB reload thay tham chiếu định kỳ)
                    injectRankings();
                }

                // Auto-save định kỳ (5 phút)
                if (System.currentTimeMillis() - lastSave > 5 * 60 * 1000L) {
                    VirtualPersistence.saveAll();
                    lastSave = System.currentTimeMillis();
                }
            } catch (InterruptedException e) {
                Thread.currentThread().interrupt();
                break;
            } catch (Exception e) {
                Logger.logException(VirtualPlayerManager.class, e);
            }
        }
    }

    /**
     * WORLD SIMULATION - Bot vẫn cày khi không ai nhìn.
     * Tăng power/chỉ số RẤT CHẬM theo expRate × activityRate × catchupPercent.
     * PLAYER PROTECTION: power bot bị chặn tại max(player thật mạnh nhất) × catchupPercent.
     * Bot luôn đuổi theo nhưng KHÔNG BAO GIỜ vượt player thật.
     */
    private void simulateProgression() {
        try {
            VirtualConfig cfg = VirtualConfig.gI();
            if (cfg.expRate <= 0) return;

            long maxRealPower = getMaxRealPlayerPower();

            for (VirtualPlayer vp : getOnlineBots()) {
                try {
                    if (vp.nPoint == null || vp.profile == null) continue;

                    float catchup = Math.max(0.05f, vp.profile.catchupPercent);
                    // Cap: nếu bật bảo vệ và biết power player thật
                    long cap;
                    if (cfg.playerProtection && maxRealPower > 0) {
                        cap = (long) (maxRealPower * catchup);
                    } else {
                        cap = Long.MAX_VALUE; // chưa có dữ liệu player -> chỉ tăng rất chậm
                    }
                    if (vp.nPoint.power >= cap) continue;

                    // Gain mỗi phút ~0.02% power hiện tại — cực chậm
                    double gainFactor = 0.0002 * vp.profile.activityRate * cfg.expRate;
                    long gain = Math.max(1, (long) (vp.nPoint.power * gainFactor));
                    long newPower = Math.min(cap, vp.nPoint.power + gain);
                    if (newPower > vp.nPoint.power) {
                        vp.nPoint.power = newPower;
                        vp.nPoint.tiemNang = newPower;

                        // Chỉ số chiến đấu tăng nhẹ theo % gain để cảm giác tiến bộ
                        float pct = gain / (float) Math.max(1, newPower);
                        int hpGain = (int) (vp.nPoint.hpg * pct * 4);
                        int dameGain = (int) (vp.nPoint.dameg * pct * 4);
                        vp.nPoint.hpg += hpGain;
                        vp.nPoint.hpMax += hpGain;
                        vp.nPoint.hp += hpGain;
                        vp.nPoint.dameg += dameGain;
                        vp.nPoint.dame += dameGain;
                    }
                } catch (Exception ignored) {}
            }
        } catch (Exception e) {
            Logger.logException(VirtualPlayerManager.class, e);
        }
    }

    /**
     * Power của player thật mạnh nhất: online hiện tại + top lịch sử từ DB.
     */
    private long getMaxRealPlayerPower() {
        long max = sessionMaxRealPower;
        try {
            for (nro.player.Player pl : nro.server.Client.gI().getPlayersSnapshot()) {
                if (pl == null || pl.isBot || pl.isBoss || pl.isDeTu || !pl.isPlayer) continue;
                if (pl.nPoint != null && pl.nPoint.power > max) max = pl.nPoint.power;
            }
        } catch (Exception ignored) {}

        // Top lịch sử từ DB (kể cả khi player offline)
        try {
            java.util.List<nro.top.TOP> top = nro.server.TopServer.TOP_SD;
            if (top != null && !top.isEmpty() && top.get(0).getPower() > max) {
                max = top.get(0).getPower();
            }
        } catch (Exception ignored) {}

        sessionMaxRealPower = max;
        return max;
    }

    private long sessionMaxRealPower;

    private long lastPresenceCheck;

    /**
     * HIỆN DIỆN LUÂN PHIÊN quanh người chơi thật.
     * Đa số bot vẫn sống ĐỘC LẬP (đúng thiết kế gốc); nhưng luôn giữ ~presencePerPlayer
     * bot HOẠT ĐỘNG trong khu của mỗi player. KHÔNG dùng bot cố định: mỗi bot chỉ "ghé thăm"
     * trong presenceVisitSeconds rồi được thả về đời sống độc lập, bot khác được kéo vào thay.
     */
    private void maintainPresence() {
        try {
            VirtualConfig cfg = VirtualConfig.gI();
            int target = cfg.presencePerPlayer;
            long now = System.currentTimeMillis();

            // 1) Thả bot đã hết lượt ghé thăm -> quay lại đời sống độc lập (sẽ tự đổi map)
            for (VirtualPlayer vp : players.values()) {
                if (vp.presenceHostId != 0 && now >= vp.presenceEndTime) {
                    vp.presenceHostId = 0;
                    vp.presenceEndTime = 0;
                }
            }
            if (target <= 0) return; // tắt hiện diện

            // 2) Người chơi thật đang online (có zone hợp lệ)
            List<Player> reals = new ArrayList<>();
            java.util.Set<nro.map.Zone> playerZones = new java.util.HashSet<>();
            for (Player pl : nro.server.Client.gI().getPlayersSnapshot()) {
                if (pl == null || pl.isBot || pl.isBoss || pl.isDeTu || !pl.isPlayer) continue;
                if (pl.zone == null || pl.zone.map == null) continue;
                reals.add(pl);
                playerZones.add(pl.zone);
            }
            if (reals.isEmpty()) return;

            // 3) Pool bot có thể điều động: online, không bận (chết/offline/chưa vào map),
            //    chưa được gán ghé thăm, và chưa đứng sẵn trong khu của player nào
            List<VirtualPlayer> pool = new ArrayList<>();
            for (VirtualPlayer vp : players.values()) {
                if (!vp.isOnline() || vp.hasActivePresence()) continue;
                if (vp.state == VirtualState.DEAD || vp.state == VirtualState.RESPAWN
                        || vp.state == VirtualState.OFFLINE || vp.state == VirtualState.SPAWN) continue;
                if (vp.zone != null && playerZones.contains(vp.zone)) continue;
                pool.add(vp);
            }
            java.util.Collections.shuffle(pool, random);

            // 4) Mỗi player: đếm bot đang trong khu, kéo thêm cho đủ target
            int poolIdx = 0;
            for (Player pl : reals) {
                try { if (pl.zone.isFullPlayer()) continue; } catch (Exception ignored) {}
                int cur = 0;
                for (VirtualPlayer vp : players.values()) {
                    if (vp.isOnline() && vp.zone == pl.zone) cur++;
                }
                int need = target - cur;
                while (need > 0 && poolIdx < pool.size()) {
                    VirtualPlayer vp = pool.get(poolIdx++);
                    if (sendToPlayerZone(vp, pl)) {
                        vp.presenceHostId = pl.id;
                        vp.presenceEndTime = now + cfg.presenceVisitSeconds * 1000L;
                        need--;
                    }
                }
            }
        } catch (Exception e) {
            Logger.logException(VirtualPlayerManager.class, e);
        }
    }

    /**
     * Điều một bot vào ĐÚNG zone của người chơi thật, xuất hiện cách player 150-400px
     * (không đè lên player). Bot vào rồi tự farm/chat/social quanh đó như người thật.
     * @return true nếu vào zone thành công
     */
    private boolean sendToPlayerZone(VirtualPlayer vp, Player host) {
        try {
            nro.map.Zone z = host.zone;
            if (z == null || z.map == null) return false;
            int side = random.nextBoolean() ? 1 : -1;
            int offset = 150 + random.nextInt(250); // 150..400px
            int x = host.location.x + side * offset;
            x = Math.max(50, Math.min(z.map.mapWidth - 50, x));
            return vp.joinZone(z, x, host.location.y);
        } catch (Exception e) {
            return false;
        }
    }

    /**
     * Đưa bot đang ghé thăm quay lại ĐÚNG khu của host (dùng sau khi bot hồi sinh giữa lượt).
     * @return true nếu host còn online và join lại thành công
     */
    public boolean joinHostZone(VirtualPlayer vp) {
        try {
            if (vp == null || vp.presenceHostId == 0) return false;
            for (Player pl : nro.server.Client.gI().getPlayersSnapshot()) {
                if (pl == null || pl.id != vp.presenceHostId) continue;
                if (pl.isBot || pl.isBoss || pl.isDeTu || !pl.isPlayer || pl.zone == null) return false;
                return sendToPlayerZone(vp, pl);
            }
        } catch (Exception ignored) {}
        return false;
    }

    /**
     * Lưu trạng thái ngay lập tức (dùng khi tắt server).
     */
    public void saveNow() {
        VirtualPersistence.saveAll();
    }

    // ===== DEBUG =====
    public String debugSummary() {
        StringBuilder sb = new StringBuilder();
        sb.append("VP Total: ").append(players.size()).append(" | Online: ").append(getOnlineBots().size()).append(" | Target: ").append(targetPopulation).append("\n");
        for (VirtualPlayer vp : players.values()) {
            sb.append("  ").append(vp.name)
              .append(" | ").append(vp.state)
              .append(" | ").append(vp.profile.describe())
              .append(" | map=").append(vp.zone != null ? vp.zone.map.mapId : "none")
              .append(" | goal=").append(vp.goals.getShortTerm())
              .append("\n");
        }
        return sb.toString();
    }
}
