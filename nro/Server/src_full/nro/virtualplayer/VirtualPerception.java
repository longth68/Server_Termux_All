package nro.virtualplayer;

import nro.map.Zone;
import nro.mob.Mob;
import nro.player.Player;
import Utils.Util;
import java.util.ArrayList;
import java.util.List;

/**
 * Hệ thống nhận thức của Virtual Player.
 * PHASE 3 - Perception.
 * Bot chỉ "quan sát" thế giới trong phạm vi giới hạn (không omniscient):
 * - Người chơi thật gần đó
 * - Bot gần đó
 * - Monster trong tầm quan sát
 * - Item trên mặt đất
 * - Mật độ người chơi trong zone
 */
public class VirtualPerception {

    private final VirtualPlayer vp;
    private final List<Player> nearbyRealPlayers = new ArrayList<>();
    private final List<Mob> nearbyMobs = new ArrayList<>();
    private final List<nro.map.ItemMap> nearbyItems = new ArrayList<>();
    private int realPlayerCountInZone = 0;
    private int mobCountInZone = 0;

    // Phạm vi quan sát (giới hạn để không biết cả map)
    private static final int VIEW_RANGE = 500;

    public VirtualPerception(VirtualPlayer vp) {
        this.vp = vp;
    }

    /**
     * Quét môi trường xung quanh VP trong zone hiện tại.
     * Chỉ biết thông tin trong VIEW_RANGE (như player thật).
     */
    public void scan() {
        nearbyRealPlayers.clear();
        nearbyMobs.clear();
        nearbyItems.clear();
        realPlayerCountInZone = 0;
        mobCountInZone = 0;

        if (vp.zone == null) return;
        Zone zone = vp.zone;

        // Player thật & bot trong zone
        try {
            for (Player pl : zone.getHumanoids()) {
                if (pl == null || pl == vp) continue;
                boolean isReal = !pl.isBot && !pl.isBoss && !pl.isDeTu && pl.isPlayer;
                if (isReal) {
                    realPlayerCountInZone++;
                    if (Util.getDistance(vp, pl) <= VIEW_RANGE) {
                        nearbyRealPlayers.add(pl);
                    }
                }
            }
        } catch (Exception ignored) {}

        // Mobs trong zone
        try {
            if (zone.mobs != null) {
                mobCountInZone = zone.mobs.size();
                for (Mob mob : zone.mobs) {
                    if (mob == null || mob.isDie()) continue;
                    if (Util.getDistance(vp, mob) <= VIEW_RANGE) {
                        nearbyMobs.add(mob);
                    }
                }
            }
        } catch (Exception ignored) {}

        // Items trên mặt đất trong tầm
        try {
            for (nro.map.ItemMap im : zone.getItemMapsForPlayer(vp)) {
                if (im == null || im.isPickedUp) continue;
                int dist = Util.getDistance(vp.location.x, vp.location.y, im.x, im.y);
                if (dist <= VIEW_RANGE) {
                    nearbyItems.add(im);
                }
            }
        } catch (Exception ignored) {}
    }

    public boolean hasRealPlayerNearby() {
        return !nearbyRealPlayers.isEmpty();
    }

    public List<Player> getNearbyRealPlayers() {
        return nearbyRealPlayers;
    }

    public List<Mob> getNearbyMobs() {
        return nearbyMobs;
    }

    public List<nro.map.ItemMap> getNearbyItems() {
        return nearbyItems;
    }

    public int getRealPlayerCountInZone() {
        return realPlayerCountInZone;
    }

    public int getMobCountInZone() {
        return mobCountInZone;
    }

    public boolean isZoneCrowded(int threshold) {
        return realPlayerCountInZone >= threshold;
    }

    public Mob getNearestMob() {
        Mob best = null;
        int bestDist = Integer.MAX_VALUE;
        for (Mob m : nearbyMobs) {
            int d = Util.getDistance(vp, m);
            if (d < bestDist) {
                bestDist = d;
                best = m;
            }
        }
        return best;
    }

    public nro.map.ItemMap getNearestItem() {
        nro.map.ItemMap best = null;
        int bestDist = Integer.MAX_VALUE;
        for (nro.map.ItemMap it : nearbyItems) {
            int d = Util.getDistance(vp.location.x, vp.location.y, it.x, it.y);
            if (d < bestDist) {
                bestDist = d;
                best = it;
            }
        }
        return best;
    }
}
