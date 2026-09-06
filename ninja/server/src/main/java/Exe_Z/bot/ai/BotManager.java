package Exe_Z.bot.ai;

import Exe_Z.bot.AutoFarmBot;
import Exe_Z.map.zones.Zone;
import Exe_Z.model.Char;
import Exe_Z.util.Log;
import Exe_Z.util.NinjaUtils;
import java.util.ArrayList;
import java.util.List;

/**
 * Port từ NRO VirtualPlayerManager: giữ population, đi theo người chơi,
 * đồng bộ level, relocate. Chạy cùng BotSweeper cũ (tương thích).
 */
public class BotManager implements Runnable {

    private static final BotManager INSTANCE = new BotManager();
    private volatile boolean running = false;
    private long lastFill = 0L;
    private long lastSave = 0L;
    private long lastSync = 0L;

    public static BotManager gI() {
        return INSTANCE;
    }

    public synchronized void start() {
        BotConfig.load();
        if (running) {
            return;
        }
        running = true;
        Thread t = new Thread(this, "NsoBotManager");
        t.setDaemon(true);
        t.start();
        System.out.println("[BOT][MANAGER] NsoBotManager started (pop=" + BotConfig.POPULATION + ")");
    }

    @Override
    public void run() {
        while (running) {
            try {
                Thread.sleep(5000L);
                if (!BotConfig.ENABLED) {
                    continue;
                }
                fillToTarget();
                accompanyRealPlayers();
                long now = System.currentTimeMillis();
                if (now - lastSync > 60000L) {
                    lastSync = now;
                    syncBotsToPlayerLevel();
                }
                if (now - lastSave > 120000L) {
                    lastSave = now;
                    saveNow();
                }
            } catch (InterruptedException e) {
                break;
            } catch (Exception e) {
                Log.error("BotManager err: " + e.getMessage(), e);
            }
        }
    }

    /** Giữ tổng số bot toàn server quanh POPULATION. */
    public void fillToTarget() {
        long now = System.currentTimeMillis();
        if (now - lastFill < 10000L) {
            return;
        }
        lastFill = now;
        int cur = AutoFarmBot.count();
        if (cur >= BotConfig.POPULATION) {
            return;
        }
        int need = Math.min(BotConfig.POPULATION - cur, 5);
        int spawnedTotal = 0;
        int maxAttempts = need * 3; // thử nhiều zone hơn số bot cần
        for (int attempt = 0; attempt < maxAttempts && spawnedTotal < need; attempt++) {
            int lv = AutoFarmBot.capLevel(50 + NinjaUtils.nextInt(0, 60));
            Zone z = BotMovement.pickAnyZone();
            if (z == null) {
                continue; // thử zone khác thay vì dừng hẳn
            }
            int[] st = AutoFarmBot.scaledStats(lv);
            int spawned = AutoFarmBot.spawnByMap(z.map.id, 1, lv, st[0], st[1], 0);
            if (spawned > 0) {
                spawnedTotal++;
            } else {
                System.out.println("[BOT][MANAGER] spawnByMap failed on map " + z.map.id + " zone " + z.id + " lv=" + lv);
            }
        }
        if (spawnedTotal > 0) {
            System.out.println("[BOT][MANAGER] fillToTarget: spawned " + spawnedTotal + "/" + need + " bots, total=" + AutoFarmBot.count());
        }
    }

    /** Bot đi theo người chơi như NRO accompanyRealPlayers/ensureBotsAroundPlayers. */
    private void accompanyRealPlayers() {
        try {
            List<Exe_Z.map.Map> maps = Exe_Z.map.MapManager.getInstance().getMaps();
            if (maps == null) {
                return;
            }
            for (Exe_Z.map.Map m : maps) {
                if (m == null) {
                    continue;
                }
                List<Zone> zones = m.getZones();
                if (zones == null) {
                    continue;
                }
                for (Zone z : zones) {
                    if (z == null) {
                        continue;
                    }
                    ensureBotsInZone(z, BotConfig.BOTS_PER_MAP);
                }
            }
        } catch (Exception e) {
            Log.error("accompany err: " + e.getMessage(), e);
        }
    }

    public void ensureBotsInZone(Zone z, int target) {
        if (z == null) {
            return;
        }
        boolean hasReal = false;
        Char anyReal = null;
        synchronized (z.players) {
            for (Char p : z.players) {
                if (BotPerception.isRealPlayer(p)) {
                    hasReal = true;
                    anyReal = p;
                    break;
                }
            }
        }
        if (!hasReal || anyReal == null) {
            return;
        }
        // Mẫu Anwin presencePerPlayer: giới hạn bot bám quanh mỗi người chơi
        int presence = BotConfig.PRESENCE_PER_PLAYER;
        if (presence > 0) {
            target = Math.min(target, presence);
        }
        int bots = AutoFarmBot.countInZone(z);
        if (bots >= target) {
            return;
        }
        int lv = AutoFarmBot.capLevel(anyReal.level);
        int[] st = AutoFarmBot.scaledStats(lv);
        AutoFarmBot.spawnByMap(z.map.id, 1, lv, st[0], st[1], 0);
    }

    private List<AutoFarmBot> snapshotBots() {
        // Truy cập qua count/spawn API; list chi tiết nằm trong AutoFarmBot (giữ tương thích)
        return new ArrayList<>();
    }

    /**
     * Đồng bộ sức mạnh kiểu NRO: dọn BOT vượt level người mạnh nhất để
     * BOT không bao giờ quá mạnh, fillToTarget sẽ spawn lại đúng tầm.
     */
    private void syncBotsToPlayerLevel() {
        try {
            int removed = AutoFarmBot.removeOverleveled();
            if (removed > 0) {
                System.out.println("[BOT][MANAGER] Da don " + removed + " bot vuot level nguoi manh nhat.");
            }
        } catch (Exception ignored) {
        }
    }

    public void relocateBot(AutoFarmBot bot) {
        if (bot == null) {
            return;
        }
        Zone z = BotMovement.pickZoneByLevel(Math.max(1, bot.level));
        if (z == null || z == bot.zone) {
            return;
        }
        try {
            bot.outZone();
            bot.joinZone(z.map.id, z.id, -1);
        } catch (Exception ignored) {
        }
    }

    public void saveNow() {
        try {
            BotPersistence.saveAll(AutoFarmBot.snapshotInfo(200));
        } catch (Exception ignored) {
        }
    }

    public void stopAll() {
        running = false;
    }
}
