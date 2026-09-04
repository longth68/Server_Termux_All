package nro.virtualplayer;

import Utils.Util;

/**
 * Di chuyển của Virtual Player.
 * PHASE 4 - Movement AI.
 * Dùng movement system của Player (không teleport trái phép), di chuyển từng bước,
 * đứng trên đất qua yPhysicInTopBot, tránh ra khỏi map.
 */
public class VirtualMovement {

    private final VirtualPlayer vp;

    public VirtualMovement(VirtualPlayer vp) {
        this.vp = vp;
    }

    /**
     * Di chuyển tới tọa độ (giới hạn trong map, đứng trên đất).
     */
    public void moveTo(int targetX, int targetY) {
        if (vp.zone == null || vp.zone.map == null) return;
        int mapW = vp.zone.map.mapWidth;
        int mapH = vp.zone.map.mapHeight;

        // Giới hạn trong map
        int x = Math.max(50, Math.min(mapW - 50, targetX));
        int y = vp.zone.map.yPhysicInTopBot(x, targetY);
        if (y < 50) y = Math.max(50, Math.min(mapH - 50, targetY));

        vp.move(vp, x, y);
    }

    /**
     * Di chuyển 1 bước về phía mục tiêu.
     */
    public void stepToward(int targetX, int targetY, int step) {
        int dx = targetX - vp.location.x;
        int dy = targetY - vp.location.y;
        double dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 1) return;
        int nx = vp.location.x + (int) (dx / dist * step);
        int ny = vp.location.y + (int) (dy / dist * step);
        moveTo(nx, ny);
    }

    /**
     * Đi lang thang trong khu vực (human imperfection - đi lệch, đổi hướng).
     */
    public void wander(int range) {
        if (vp.zone == null) return;
        int nx = vp.location.x + Util.nextInt(-range, range);
        int ny = vp.location.y + Util.nextInt(-range, range);
        moveTo(nx, ny);
    }

    public boolean isNear(int targetX, int targetY, int range) {
        return Util.getDistance(vp.location.x, vp.location.y, targetX, targetY) <= range;
    }

    public boolean isNear(nro.mob.Mob mob, int range) {
        return Util.getDistance(vp, mob) <= range;
    }
}
