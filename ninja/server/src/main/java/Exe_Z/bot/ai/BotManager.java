package Exe_Z.bot.ai;

import Exe_Z.bot.AutoFarmBot;
import Exe_Z.map.zones.Zone;
import Exe_Z.model.Char;
import Exe_Z.util.Log;
import Exe_Z.util.NinjaUtils;
import java.util.ArrayList;
import java.util.List;

/**
 * Port từ NRO VirtualPlayerManager — BotPopulationManager + BotSpawnScheduler.
 * Population scale theo số player online (base + perPlayer×online, trần max).
 * Spawn GRADUAL: mỗi bot sinh cách nhau delay ngẫu nhiên min..max (yêu cầu 4/6/23).
 * Đồng bộ progression (BOT < PLAYER) mỗi 60s. Không follow player (luật).
 */
public class BotManager implements Runnable {

    private static final BotManager INSTANCE = new BotManager();
    private volatile boolean running = false;
    private long lastFill = 0L;
    private long lastSave = 0L;
    private long lastSync = 0L;
    /** Thời điểm được phép sinh bot tiếp theo (spawn scheduler). */
    private long nextSpawnAt = 0L;

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

    /** Population mong muốn theo mode: base + perPlayer×online, trần POPULATION. */
    public int desiredPopulation() {
        int online;
        try {
            online = Exe_Z.server.ServerManager.getNumberOnline();
        } catch (Exception e) {
            online = 0;
        }
        String mode = BotConfig.SPAWN_MODE;
        if ("PREEXISTING".equals(mode) || "PRE_EXISTING".equals(mode)) {
            return Math.max(BotConfig.POP_BASE, BotConfig.POPULATION);
        }
        // GRADUAL / MIXED / DYNAMIC: scale theo player online
        int desired = BotConfig.POP_BASE + BotConfig.POP_PER_PLAYER * online;
        return Math.min(desired, BotConfig.POPULATION);
    }

    /**
     * BotSpawnScheduler + population fill (yêu cầu 4/5/6/23).
     * Mỗi lần chỉ sinh 1 bot, cách nhau delay ngẫu nhiên min..max config —
     * không spawn loạt cùng lúc.
     */
    public void fillToTarget() {
        long now = System.currentTimeMillis();
        if (now < nextSpawnAt) {
            return;
        }
        int cur = AutoFarmBot.count();
        int desired = desiredPopulation();
        if (cur >= desired) {
            return;
        }
        nextSpawnAt = now + NinjaUtils.nextInt(BotConfig.SPAWN_MIN_DELAY, BotConfig.SPAWN_MAX_DELAY);
        int lv = Exe_Z.bot.ai.BotProgressionController.spawnLevel();
        Zone z = BotMovement.pickAnyZone();
        if (z == null) {
            return;
        }
        int[] st = AutoFarmBot.scaledStats(lv);
        int spawned = AutoFarmBot.spawnByMap(z.map.id, 1, lv, st[0], st[1], 0);
        if (spawned > 0) {
            System.out.println("[BOT-SPAWN] map=" + z.map.id + " zone=" + z.id
                    + " lv=" + lv + " reason=population"
                    + " total=" + AutoFarmBot.count() + "/" + desired);
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
                System.out.println("[BOT-PROGRESSION] removed " + removed
                        + " overleveled bots (playerMax=" + AutoFarmBot.maxOnlineRealLevel() + ")");
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
