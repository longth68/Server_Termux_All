package nro.virtualplayer;

import nro.player.Player;
import nro.services.Service;
import nro.virtualplayer.core.VirtualPersonality;
import Utils.Util;

/**
 * Chat AI cho Virtual Player.
 * PHASE 5 - Chat.
 * Chat theo CONTEXT, có cooldown, tránh spam, theo personality,
 * không lặp câu liên tục (dùng VirtualMemory.pickChat).
 *
 * Chỉ chat khi có Player thật gần đó, tần suất theo talkativeness.
 */
public class VirtualChat {

    private final VirtualPlayer vp;
    private long lastTimeChat;
    private long lastGreetTime;
    private int timesGreeted = 0;

    public VirtualChat(VirtualPlayer vp) {
        this.vp = vp;
    }

    /**
     * Tick chat. Duoc goi tu VirtualBrain moi vai giay (khong phai moi tick AI).
     * Chat voi player that hoac bot khac trong cung zone.
     */
    public void updateChat() {
        if (vp.zone == null || vp.isDie()) return;
        if (vp.profile == null) return;

        // Tim player that hoac bot khac gan day
        Player nearby = findNearbyRealPlayer();
        VirtualPlayer nearbyBot = (nearby == null) ? findNearbyBot() : null;

        // Khong co ai gan day -> khong chat
        if (nearby == null && nearbyBot == null) return;

        // Cooldown theo talkativeness: 8s (noi nhieu) den 45s (it noi)
        float talk = vp.profile.getTalkativeness() * Math.max(0.3f, VirtualConfig.gI().chatRate * 2f);
        long cooldown = (long) (8000 + (1f - talk) * 37000);
        if (System.currentTimeMillis() - lastTimeChat < cooldown) return;

        // Xac suat noi (tranh spam 100%)
        if (!Util.isTrue((int) (talk * 65), 100)) {
            lastTimeChat = System.currentTimeMillis();
            return;
        }

        // Cau chat tuy chinh tu Web Admin (neu co): uu tien dung 60% so lan
        String msg;
        if (!VirtualChatConfig.gI().isEmpty() && Util.isTrue(60, 100)) {
            msg = VirtualChatConfig.gI().randomLine();
        } else if (nearby != null) {
            msg = buildContextualMessage(nearby);
        } else {
            msg = buildBotToBotMessage(nearbyBot);
        }
        if (msg != null && !msg.isEmpty()) {
            sendChat(msg);
            vp.memory.rememberChat(msg);
            lastTimeChat = System.currentTimeMillis();
        }
    }

    /**
     * Tim Player that gan nhat trong zone (radius 600px).
     */
    private Player findNearbyRealPlayer() {
        if (vp.zone == null) return null;
        for (Player pl : vp.zone.getPlayers()) {
            if (pl == null || pl == vp || pl.isBot || pl.isBoss || pl.isDeTu) continue;
            if (!pl.isPlayer) continue;
            int dist = Util.getDistance(vp, pl);
            if (dist <= 600) {
                return pl;
            }
        }
        return null;
    }

    /**
     * Tim bot khac gan day nhat trong cung zone de chat (radius 400px).
     */
    private VirtualPlayer findNearbyBot() {
        if (vp.zone == null) return null;
        for (Player pl : vp.zone.getPlayers()) {
            if (pl == null || pl == vp) continue;
            if (!pl.isBot || !(pl instanceof VirtualPlayer)) continue;
            int dist = Util.getDistance(vp, pl);
            if (dist <= 400) {
                return (VirtualPlayer) pl;
            }
        }
        return null;
    }

    /**
     * Xây dựng câu chat theo context hiện tại + personality.
     */
    private String buildContextualMessage(Player nearby) {
        if (nearby == null) return null;

        // Mối quan hệ với player này
        String name = nearby.name;
        boolean isFriend = vp.memory.isFriend(name);
        boolean isRival = vp.memory.isRival(name);
        boolean greeted = vp.memory.hasPartiedWith(name);
        boolean talkedBefore = vp.memory.hasSaidRecently(name);

        // Nếu chưa từng nói chuyện lần nào -> chào
        if (!talkedBefore && timesGreeted < 3) {
            timesGreeted++;
            lastGreetTime = System.currentTimeMillis();
            if (isFriend) {
                return vp.memory.pickChat(new String[]{
                    "Lại gặp huynh rồi!",
                    "Chào huynh, dạo này thế nào?",
                    "Huynh khỏe không?"
                });
            }
            if (isRival) {
                return vp.memory.pickChat(new String[]{
                    "Chà, gặp lại huynh rồi.",
                    "Lần này ta sẽ thắng huynh."
                });
            }
            return vp.memory.pickChat(new String[]{
                "Chào huynh!",
                "Xin chào!",
                "Chào, huynh đi đâu thế?"
            });
        }

        // Theo personality
        if (vp.profile.hasPersonality(VirtualPersonality.FARMER)) {
            return vp.memory.pickChat(new String[]{
                "Quái ở đây hôm nay đông thật.",
                "Farm mãi mà chưa đủ đồ.",
                "Chỗ này farm cũng được đấy huynh."
            });
        }
        if (vp.profile.hasPersonality(VirtualPersonality.TRADER)) {
            return vp.memory.pickChat(new String[]{
                "Huynh có muốn xem vài món ta đang có không?",
                "Hàng của ta giá cả phải chăng.",
                "Có item gì cần đổi không?"
            });
        }
        if (vp.profile.hasPersonality(VirtualPersonality.COMPETITIVE)) {
            return vp.memory.pickChat(new String[]{
                "Ta nhất định sẽ đuổi kịp huynh.",
                "Rank của huynh đang cao đấy, nhưng ta sẽ vượt.",
                "Đợi đấy, ta sẽ mạnh hơn huynh."
            });
        }
        if (vp.profile.hasPersonality(VirtualPersonality.SOCIAL)) {
            return vp.memory.pickChat(new String[]{
                "Hôm nay đi đâu chơi thế?",
                "Có ai đi dungeon cùng không?",
                "Khuya nay có sự kiện gì không?"
            });
        }
        if (vp.profile.hasPersonality(VirtualPersonality.BEGINNER)) {
            return vp.memory.pickChat(new String[]{
                "Bao giờ ta mới mạnh được như huynh nhỉ...",
                "Map này hơi khó với ta.",
                "Huynh farm nhanh thật."
            });
        }
        if (vp.profile.hasPersonality(VirtualPersonality.QUIET)) {
            return null; // Người ít nói: không nói gì
        }

        // Trạng thái hiện tại
        if (vp.isDie()) {
            return vp.memory.pickChat(new String[]{
                "Con này mạnh hơn ta tưởng...",
                "Xui quá, bị quái hạ gục."
            });
        }
        if (vp.state == nro.virtualplayer.core.VirtualState.GO_SHOP) {
            return vp.memory.pickChat(new String[]{
                "Ta về thành mua đồ đây.",
                "Cần mua ít đậu thần rồi."
            });
        }
        if (vp.state == nro.virtualplayer.core.VirtualState.REST) {
            return vp.memory.pickChat(new String[]{
                "Nghỉ chút đã, mệt quá.",
                "Ngồi nghỉ xíu rồi farm tiếp."
            });
        }

        // Cau mac dinh
        return vp.memory.pickChat(new String[]{
            "Chỗ này hôm nay đông thật.",
            "Hôm nay vận đỏ nhỉ.",
            "Đang làm nhiệm vụ dài quá."
        });
    }

    /**
     * Xay dung cau chat bot-to-bot (khong can player that).
     */
    private String buildBotToBotMessage(VirtualPlayer other) {
        if (other == null) return null;
        String name = other.name;
        if (vp.memory.hasSaidRecently(name)) return null;

        if (Util.isTrue(30, 100)) {
            return vp.memory.pickChat(new String[]{
                "Chào " + name + "!",
                name + " đang farm ở đây à?",
                "Hay nhỉ, gặp cả " + name + " ở đây."
            });
        }
        return vp.memory.pickChat(new String[]{
            "Quái ở đây nhiều ghê.",
            "Farm tiếp thôi.",
            "Nhiệm vụ hôm nay dài quá.",
            "Xem nào, cần đi đâu tiếp...",
            "Đang kiếm đồ mà chưa thấy rơi."
        });
    }

    private void sendChat(String msg) {
        try {
            Service.gI().chat(vp, msg);
        } catch (Exception ignored) {}
    }
}