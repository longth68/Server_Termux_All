package nro.virtualplayer;

import nro.mob.Mob;
import nro.player.Player;
import nro.skill.SkillService;
import nro.services.Fun.UseItem;
import Utils.Util;

/**
 * Combat AI của Virtual Player.
 * PHASE 4 - Combat.
 * - Chọn target hợp lệ (còn sống, không bị player thật chiếm)
 * - Nhường quái cho Player thật (REAL PLAYER > BOT)
 * - Dùng skill giống player
 * - Heal khi HP thấp, retreat khi nguy hiểm
 */
public class VirtualCombat {

    private final VirtualPlayer vp;
    private Mob targetMob;

    public VirtualCombat(VirtualPlayer vp) {
        this.vp = vp;
    }

    /**
     * Tìm quái hợp lệ. Nhường quái cho player thật.
     */
    public Mob findTarget() {
        targetMob = null;
        if (vp.zone == null || vp.zone.mobs == null || vp.zone.mobs.isEmpty()) return null;

        Mob best = null;
        int bestDist = Integer.MAX_VALUE;

        for (Mob mob : vp.zone.mobs) {
            if (mob == null || mob.isDie()) continue;
            int dist = Util.getDistance(vp, mob);

            // Nhường quái: nếu player thật đứng sát quái thì bỏ qua
            if (isReservedByPlayer(mob)) {
                continue;
            }

            if (dist < bestDist) {
                bestDist = dist;
                best = mob;
            }
        }

        targetMob = best;
        return best;
    }

    /**
     * Kiểm tra quái có đang bị người chơi thật "chiếm" không.
     * PLAYER > BOT là quy tắc bắt buộc:
     * 1. Player thật đã đánh mob này (có trong temporaryEnemies) -> nhường
     * 2. Player thật đang đứng sát mob (chuẩn bị đánh) -> nhường
     */
    private boolean isReservedByPlayer(Mob mob) {
        // 1. Mob đang bị player thật tấn công
        try {
            if (mob.temporaryEnemies != null) {
                for (Player pl : mob.temporaryEnemies) {
                    if (pl == null || pl.isBot || pl.isBoss || pl.isDeTu || !pl.isPlayer) continue;
                    return true; // Player thật đã chiếm mob này
                }
            }
        } catch (Exception ignored) {}

        // 2. Player thật đứng sát mob
        try {
            for (Player pl : vp.zone.getPlayers()) {
                if (pl == null || pl.isBot || pl.isBoss || pl.isDeTu || !pl.isPlayer) continue;
                if (Util.getDistance(pl.location.x, pl.location.y, mob.location.x, mob.location.y) < 60) {
                    return true;
                }
            }
        } catch (Exception ignored) {}
        return false;
    }

    public Mob getTargetMob() {
        return targetMob;
    }

    public void setTarget(Mob mob) {
        this.targetMob = mob;
    }

    /**
     * Tấn công quái bằng SkillService (giống player).
     * Trả về true nếu đã thực hiện skill.
     */
    public boolean attackTarget() {
        if (targetMob == null || targetMob.isDie()) {
            targetMob = null;
            return false;
        }

        // Chọn skill tấn công (ưu tiên skill đầu tiên, thỉnh thoảng đổi)
        if (vp.playerSkill.skillSelect == null || Util.isTrue(20, 100)) {
            selectAttackSkill();
        }

        if (vp.playerSkill.skillSelect != null) {
            try {
                SkillService.gI().useSkill(vp, null, targetMob, -1, null);
            } catch (Exception ignored) {}
            return true;
        }
        return false;
    }

    private void selectAttackSkill() {
        if (vp.playerSkill.skills == null || vp.playerSkill.skills.isEmpty()) return;
        if (vp.playerSkill.skills.size() == 1) {
            vp.playerSkill.skillSelect = vp.playerSkill.skills.get(0);
        } else {
            vp.playerSkill.skillSelect = vp.playerSkill.skills.get(Util.nextInt(0, vp.playerSkill.skills.size() - 1));
        }
    }

    /**
     * Kiểm tra và hồi máu bằng đậu thần khi HP thấp.
     */
    public boolean checkAndHeal() {
        if (vp.isDie()) return false;
        if (vp.nPoint == null) return false;
        if (vp.nPoint.hp < vp.nPoint.hpMax * 0.25) {
            int[] buffPea = {1, 2, 3, 4, 5, 6, 7, 8, 9};
            int option = buffPea[Util.nextInt(0, buffPea.length - 1)];
            try {
                UseItem.gI().eatPeaBot(vp, option);
                return true;
            } catch (Exception e) {
                // bỏ qua
            }
        }
        return false;
    }

    /**
     * Trả về true nếu HP rất thấp -> cần bỏ chạy (retreat).
     */
    public boolean shouldRetreat() {
        return vp.nPoint != null && vp.nPoint.hp < vp.nPoint.hpMax * 0.15;
    }

    /**
     * Trả về true nếu HP tương đối ổn.
     */
    public boolean isHealthy() {
        return vp.nPoint != null && vp.nPoint.hp > vp.nPoint.hpMax * 0.5;
    }
}
