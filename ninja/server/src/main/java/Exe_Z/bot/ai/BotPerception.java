package Exe_Z.bot.ai;

import Exe_Z.bot.Bot;
import Exe_Z.map.item.ItemMap;
import Exe_Z.map.zones.Zone;
import Exe_Z.mob.Mob;
import Exe_Z.model.Char;
import Exe_Z.util.NinjaUtils;
import java.util.ArrayList;
import java.util.List;

/**
 * Port từ NRO VirtualPerception: nhận biết môi trường NSO.
 * Dùng API Zone/players/monsters/ItemMap sẵn có, tôn trọng
 * player_protection (nhường quái cho người chơi thật).
 */
public class BotPerception {

    public static boolean isRealPlayer(Char p) {
        if (p == null || p instanceof Bot || p.isDead) {
            return false;
        }
        return p.user != null && p.user.session != null;
    }

    public static List<Char> realPlayersInZone(Zone z) {
        List<Char> out = new ArrayList<>();
        if (z == null || z.players == null) {
            return out;
        }
        synchronized (z.players) {
            for (Char p : z.players) {
                if (isRealPlayer(p)) {
                    out.add(p);
                }
            }
        }
        return out;
    }

    public static Char nearestRealPlayer(Char bot, int range) {
        Zone z = bot == null ? null : bot.zone;
        if (z == null) {
            return null;
        }
        Char best = null;
        int bd = Integer.MAX_VALUE;
        synchronized (z.players) {
            for (Char p : z.players) {
                if (p == null || p == bot || !isRealPlayer(p)) {
                    continue;
                }
                int d = NinjaUtils.getDistance(bot.x, bot.y, p.x, p.y);
                if (d <= range && d < bd) {
                    bd = d;
                    best = p;
                }
            }
        }
        return best;
    }

    /** Quái bot được phép đánh: sống, không boss, không bị người chơi giữ. */
    public static Mob findMobTarget(Char bot, int range) {
        Zone z = bot == null ? null : bot.zone;
        if (z == null) {
            return null;
        }
        List<Mob> mobs;
        try {
            mobs = z.getLivingMonsters();
        } catch (Exception e) {
            mobs = z.monsters;
        }
        if (mobs == null) {
            return null;
        }
        Mob best = null;
        int bd = Integer.MAX_VALUE;
        for (Mob m : mobs) {
            if (m == null || m.isDead || m.isBoss || m.levelBoss != 0) {
                continue;
            }
            if (isReservedByPlayer(bot, m)) {
                continue;
            }
            int d = NinjaUtils.getDistance(bot.x, bot.y, m.x, m.y);
            if (d <= range && d < bd) {
                bd = d;
                best = m;
            }
        }
        return best;
    }

    /** Nhường quái trong vòng player_protection px quanh người chơi thật. */
    public static boolean isReservedByPlayer(Char bot, Mob mob) {
        if (bot == null || mob == null || bot.zone == null) {
            return false;
        }
        int r = BotConfig.PLAYER_PROTECTION_PX;
        if (r <= 0) {
            return false;
        }
        synchronized (bot.zone.players) {
            for (Char p : bot.zone.players) {
                if (!isRealPlayer(p) || p == bot) {
                    continue;
                }
                int dp = NinjaUtils.getDistance(p.x, p.y, mob.x, mob.y);
                if (dp <= r) {
                    int db = NinjaUtils.getDistance(bot.x, bot.y, mob.x, mob.y);
                    // Người chơi gần quái hơn bot -> nhường
                    if (dp < db) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public static ItemMap findNearItem(Char bot, int range) {
        Zone z = bot == null ? null : bot.zone;
        if (z == null) {
            return null;
        }
        ItemMap best = null;
        int bd = range;
        try {
            for (ItemMap im : z.getItemMaps()) {
                if (im == null || im.isPickedUp() || im.isExpired()) {
                    continue;
                }
                int owner = im.getOwnerID();
                if (owner != -1 && owner != bot.id && !im.isCanPickup()) {
                    continue;
                }
                int d = NinjaUtils.getDistance(bot.x, bot.y, im.getX(), im.getY());
                if (d < bd) {
                    bd = d;
                    best = im;
                }
            }
        } catch (Exception ignored) {
        }
        return best;
    }

    /** Mob đang đánh người chơi thật gần bot -> bot ưu tiên hỗ trợ. */
    public static Mob findMobAttackingRealPlayer(Char bot) {
        // NSO Mob không expose target trực tiếp, heuristic: mob gần người chơi nhất
        Char p = nearestRealPlayer(bot, 300);
        if (p == null) {
            return null;
        }
        Zone z = bot.zone;
        Mob best = null;
        int bd = Integer.MAX_VALUE;
        try {
            for (Mob m : z.getLivingMonsters()) {
                if (m == null || m.isDead || m.isBoss) {
                    continue;
                }
                int d = NinjaUtils.getDistance(p.x, p.y, m.x, m.y);
                if (d < 150 && d < bd) {
                    bd = d;
                    best = m;
                }
            }
        } catch (Exception ignored) {
        }
        return best;
    }
}
