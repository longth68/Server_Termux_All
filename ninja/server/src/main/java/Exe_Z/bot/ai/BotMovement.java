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

    public static Zone pickZoneByLevel(int level) {
        try {
            java.util.List<Exe_Z.map.Map> maps = Exe_Z.map.MapManager.getInstance().getMaps();
            if (maps == null) {
                return null;
            }
            // MAPS theo level như NRO MAPS_LOW/MID/HIGH (NSO map id farm phổ biến)
            int[] pool;
            if (level < 30) {
                pool = new int[]{1, 2, 3, 4, 5, 6, 7};
            } else if (level < 70) {
                pool = new int[]{11, 12, 13, 14, 15, 16, 20, 21};
            } else {
                pool = new int[]{23, 24, 25, 26, 27, 28, 29, 30, 31};
            }
            for (int mid : pool) {
                Exe_Z.map.Map m = Exe_Z.map.MapManager.getInstance().find(mid);
                if (m != null) {
                    try {
                        Zone z = m.rand();
                        if (z != null) {
                            return z;
                        }
                    } catch (Exception ignored) {
                    }
                }
            }
        } catch (Exception ignored) {
        }
        return null;
    }
}
