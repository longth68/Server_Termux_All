package nro.virtualplayer;

import nro.services.Service;
import nro.virtualplayer.core.VirtualPersonality;
import Utils.Util;

/**
 * Tương tác giữa các Virtual Player với nhau.
 * PHASE 7 - Bot-to-Bot social.
 *
 * Bot gặp Bot khác -> chào hỏi, hình thành FRIEND/RIVAL qua memory,
 * chat cho player thật ở gần nghe thấy -> thế giới sống động hơn.
 *
 * Chỉ dùng cơ chế có sẵn: Service.chat + VirtualMemory.
 */
public class VirtualSocial {

    private final VirtualPlayer vp;
    private long lastInteractTime;

    public VirtualSocial(VirtualPlayer vp) {
        this.vp = vp;
    }

    /**
     * Tick xã hội. Được gọi từ Brain với cooldown riêng (~30s).
     */
    public void update() {
        if (vp.zone == null || vp.isDie()) return;
        if (vp.profile == null || vp.memory == null) return;
        long now = System.currentTimeMillis();
        if (now - lastInteractTime < 30000) return;

        // QUIET ít tương tác; SOLO tránh mặt bot khác
        if (vp.profile.hasPersonality(VirtualPersonality.QUIET)
                && !Util.isTrue(15, 100)) {
            lastInteractTime = now;
            return;
        }
        if (vp.profile.hasPersonality(VirtualPersonality.SOLO)) {
            lastInteractTime = now;
            return;
        }

        lastInteractTime = now;
        VirtualPlayer other = findNearbyBot();
        if (other == null) return;

        interact(other);
    }

    /**
     * Tìm bot khác gần nhất trong zone.
     */
    private VirtualPlayer findNearbyBot() {
        VirtualPlayer best = null;
        int bestDist = Integer.MAX_VALUE;
        for (VirtualPlayer p : VirtualPlayerManager.gI().getOnlineBots()) {
            if (p == null || p == vp || p.zone == null) continue;
            if (!p.zone.equals(vp.zone)) continue;
            int dist = Util.getDistance(vp, p);
            if (dist <= 400 && dist < bestDist) {
                bestDist = dist;
                best = p;
            }
        }
        return best;
    }

    /**
     * Gặp gỡ: cập nhật quan hệ và đôi khi nói chuyện.
     */
    private void interact(VirtualPlayer other) {
        String name = other.name;

        // Quan hệ tiến triển theo lần gặp
        if (vp.profile.hasPersonality(VirtualPersonality.COMPETITIVE)
                && other.nPoint != null && vp.nPoint != null
                && Math.abs(other.nPoint.power - vp.nPoint.power) < other.nPoint.power * 0.3f) {
            // Sức mạnh ngang ngửa + thích cạnh tranh -> rival
            if (!vp.memory.isFriend(name) && Util.isTrue(25, 100)) {
                vp.memory.adjustRelation(name, -12);
            }
        } else {
            // Bình thường: càng gặp càng thân
            float score = vp.memory.getRelationScore(name);
            if (score < 40 && Util.isTrue(50, 100)) {
                vp.memory.adjustRelation(name, 2);
            }
        }

        // Đôi khi chào nhau (player thật gần đó sẽ thấy thế giới sống động)
        boolean isFriend = vp.memory.isFriend(name);
        boolean isRival = vp.memory.isRival(name);
        int chance = isFriend ? 35 : isRival ? 20 : 10;
        if (!Util.isTrue(chance, 100)) return;
        // TALKATIVE hay nói hơn
        if (vp.profile.hasPersonality(VirtualPersonality.TALKATIVE)) chance *= 2;

        String[] lines;
        if (isFriend) {
            lines = new String[]{
                "Lâu rồi không gặp, " + name + "!",
                name + ", dạo này cày ở đâu vậy?",
                "Chào " + name + ", khỏe không?",
                "Lại gặp cậu rồi."
            };
        } else if (isRival) {
            lines = new String[]{
                name + ", lần này ta sẽ hơn cậu.",
                "Đừng để ta vượt mặt nhé " + name + ".",
                "Gặp lại rồi đấy, " + name + "."
            };
        } else {
            lines = new String[]{
                "Chào " + name + ".",
                "Farm gì ở đây vậy?",
                "Chỗ này đông nhỉ."
            };
        }

        String msg = vp.memory.pickChat(lines);
        try {
            Service.gI().chat(vp, msg);
            vp.needs.satisfySocial();
        } catch (Exception ignored) {}
    }
}