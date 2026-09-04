package nro.virtualplayer;

import Utils.Util;
import nro.services.Service;
import nro.virtualplayer.core.VirtualState;

/**
 * Port tu Hashirama: bot the hien cam xuc bang icon + cau noi phia tren dau.
 * Icon 1139-1148 (client hien thi san, Service.sendBigMessage da ho tro).
 */
public class VirtualEmotion {

    public static final int ICON_HAPPY = 1139;
    public static final int ICON_SAD = 1140;
    public static final int ICON_ANGRY = 1141;
    public static final int ICON_SURPRISED = 1142;
    public static final int ICON_CRY = 1143;
    public static final int ICON_LAUGH = 1144;
    public static final int ICON_SLEEP = 1145;
    public static final int ICON_CONFUSED = 1146;
    public static final int ICON_COOL = 1147;
    public static final int ICON_HEART = 1148;

    public static final long EMOTE_COOLDOWN = 15_000L;

    public static void tick(VirtualPlayer vp, VirtualPerception perception) {
        try {
            if (vp == null || vp.zone == null) return;
            long now = System.currentTimeMillis();
            if (now - vp.lastEmote < EMOTE_COOLDOWN) return;
            if (vp.nPoint.hp <= 0) return;

            boolean hasNearReal = perception != null && !perception.getNearbyRealPlayers().isEmpty();
            int roll = Util.nextInt(0, 100);
            if (!hasNearReal && roll < 60) return;

            String text = null;
            int iconId = -1;
            if (vp.nPoint.hp < vp.nPoint.hpMax * 0.2) {
                iconId = ICON_SAD;
                text = "Met qua...";
            } else if (vp.nPoint.hp < vp.nPoint.hpMax * 0.5) {
                iconId = ICON_CONFUSED;
                text = "Co len nao...";
            } else if (vp.state == VirtualState.REST) {
                iconId = ICON_SLEEP;
                text = "Zzzzz...";
            } else if (hasNearReal && roll < 15) {
                iconId = ICON_HAPPY;
                text = "Hihi :)";
            } else if (hasNearReal && roll < 30) {
                iconId = ICON_COOL;
                text = "Cay day!";
            } else if (hasNearReal && roll < 40) {
                iconId = ICON_HEART;
                text = "Cam on nhe <3";
            } else if (roll < 10) {
                iconId = ICON_LAUGH;
                text = "Haha!";
            }
            if (iconId > 0 && text != null) {
                vp.lastEmote = now;
                Service.gI().sendBigMessage(vp, iconId, text);
            }
        } catch (Exception ignored) {
        }
    }
}
