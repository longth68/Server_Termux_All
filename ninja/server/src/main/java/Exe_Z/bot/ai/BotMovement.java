package Exe_Z.bot.ai;

import Exe_Z.bot.AutoFarmBot;
import Exe_Z.map.zones.Zone;
import Exe_Z.util.NinjaUtils;

/**
 * Port từ NRO VirtualMovement: di chuyển có giới hạn map, wander.
 */
public class BotMovement {

    public static void moveToward(AutoFarmBot bot, int tx, int ty) {
        if (bot != null) {
            bot.aiMoveTo(tx, ty);
        }
    }

    public static void wander(AutoFarmBot bot) {
        if (bot == null || bot.zone == null) {
            return;
        }
        // LAZY đi ít hơn
        if (bot.botProfile.laziness > 0.6f && NinjaUtils.nextInt(0, 100) < 50) {
            return;
        }
        int[] b = bot.aiMapBounds();
        int w = Math.max(120, b[1] - b[0]);
        int h = Math.max(80, b[3] - b[2]);
        int rangeX = Math.max(60, w / 8);
        int rangeY = Math.max(40, h / 8);
        // EXPLORER đi xa hơn
        if (bot.botProfile.personalities.contains(BotPersonality.EXPLORER)) {
            rangeX *= 2;
            rangeY *= 2;
        }
        int nx = bot.x + NinjaUtils.nextInt(-rangeX, rangeX);
        int ny = bot.y + NinjaUtils.nextInt(-rangeY, rangeY);
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
        bot.aiMoveTo(nx, ny);
    }

    public static void wanderVillage(AutoFarmBot bot) {
        if (bot == null) {
            return;
        }
        int nx = bot.x + NinjaUtils.nextInt(-50, 50);
        int ny = bot.y + NinjaUtils.nextInt(-20, 20);
        bot.aiMoveTo(nx, ny);
    }

    private static final int[] VILLAGE_MAP_IDS = {10, 17, 22, 32, 38, 43, 48, 138, 162};

    private static boolean isVillageMap(int mapId) {
        for (int v : VILLAGE_MAP_IDS) {
            if (v == mapId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Chọn zone từ map ĐÃ LOAD thật (không dùng ID cứng để khỏi lệch DB).
     * Ưu tiên: 1) zone đang có người chơi thật (presence như NRO),
     * 2) map farm có quái sống, tránh làng/map đặc biệt.
     */
    public static Zone pickZoneByLevel(int level) {
        try {
            java.util.List<Exe_Z.map.Map> maps = Exe_Z.map.MapManager.getInstance().getMaps();
            if (maps == null || maps.isEmpty()) {
                return null;
            }
            // 1. Presence: zone có người chơi thật
            java.util.List<Zone> withPlayers = new java.util.ArrayList<>();
            for (Exe_Z.map.Map m : maps) {
                if (m == null || isVillageMap(m.id)) {
                    continue;
                }
                java.util.List<Zone> zones = m.getZones();
                if (zones == null) {
                    continue;
                }
                for (Zone z : zones) {
                    if (z == null || z.players == null) {
                        continue;
                    }
                    synchronized (z.players) {
                        for (Exe_Z.model.Char p : z.players) {
                            if (BotPerception.isRealPlayer(p)) {
                                withPlayers.add(z);
                                break;
                            }
                        }
                    }
                }
            }
            if (!withPlayers.isEmpty()) {
                return withPlayers.get(Exe_Z.util.NinjaUtils.nextInt(0, withPlayers.size() - 1));
            }
            // 2. Map farm có quái sống
            java.util.List<Zone> farmZones = new java.util.ArrayList<>();
            for (Exe_Z.map.Map m : maps) {
                if (m == null || isVillageMap(m.id)) {
                    continue;
                }
                java.util.List<Zone> zones = m.getZones();
                if (zones == null || zones.isEmpty()) {
                    continue;
                }
                for (Zone z : zones) {
                    if (z == null || z.monsters == null || z.monsters.isEmpty()) {
                        continue;
                    }
                    farmZones.add(z);
                }
            }
            if (!farmZones.isEmpty()) {
                return farmZones.get(Exe_Z.util.NinjaUtils.nextInt(0, farmZones.size() - 1));
            }
            // 3. Fallback: zone bất kỳ không phải làng
            for (Exe_Z.map.Map m : maps) {
                if (m == null || isVillageMap(m.id)) {
                    continue;
                }
                java.util.List<Zone> zones = m.getZones();
                if (zones != null && !zones.isEmpty() && zones.get(0) != null) {
                    return zones.get(0);
                }
            }
        } catch (Exception ignored) {
        }
        return null;
    }
}
