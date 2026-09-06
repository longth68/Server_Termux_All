package Exe_Z.bot.ai;

import Exe_Z.bot.AutoFarmBot;
import Exe_Z.util.NinjaUtils;

/**
 * Port từ NRO playerProtection mở rộng — LUẬT CAO NHẤT: BOT < PLAYER.
 *
 * Kiểm soát ceiling theo: Level, Power (HP/dame qua scaledStats ratio),
 * Equipment (createBot chỉ chọn đồ theo level đã cap), Quest (progression dừng).
 * Khi đạt ceiling: STOP_PROGRESSION — bot vẫn move/farm/chat/trade/social
 * nhưng KHÔNG tăng progression (level bị kẹp, exp bị giảm hiệu quả).
 */
public class BotProgressionController {

    /** Level người chơi thật mạnh nhất đang online (tái dùng AutoFarmBot). */
    public static int playerRefLevel() {
        return AutoFarmBot.maxOnlineRealLevel();
    }

    /** Gap riêng của bot này so với player (ngẫu nhiên min..max gap lúc spawn). */
    public static int gapFor(AutoFarmBot bot) {
        if (bot == null || bot.progressionGap <= 0) {
            return NinjaUtils.nextInt(BotConfig.PROG_MIN_GAP, BotConfig.PROG_MAX_GAP);
        }
        return bot.progressionGap;
    }

    /**
     * Trần level cho bot: maxReal - gap. Không ai online → Integer.MAX_VALUE
     * (bot tự do vì luật chỉ áp khi có player tham chiếu).
     */
    public static int ceilingLevel(AutoFarmBot bot) {
        int maxReal = playerRefLevel();
        if (maxReal <= 0) {
            return Integer.MAX_VALUE;
        }
        return Math.max(1, maxReal - gapFor(bot));
    }

    /** Spawn level: nằm trong khoảng (maxReal - maxGap) .. (maxReal - minGap). */
    public static int spawnLevel() {
        int maxReal = playerRefLevel();
        if (maxReal <= 0) {
            // Pre-existing world: level phân bố rộng như người chơi thật
            return 10 + NinjaUtils.nextInt(0, 90);
        }
        int hi = Math.max(1, maxReal - BotConfig.PROG_MIN_GAP);
        int lo = Math.max(1, maxReal - BotConfig.PROG_MAX_GAP);
        return NinjaUtils.nextInt(Math.min(lo, hi), Math.max(lo, hi));
    }

    /** Đã đạt ceiling → STOP_PROGRESSION (giảm hiệu quả farm, tăng nghỉ). */
    public static boolean atCeiling(AutoFarmBot bot) {
        return bot != null && bot.level >= ceilingLevel(bot);
    }

    /**
     * Yêu cầu 13: gần ceiling thì tự giảm farm efficiency — bơm REST
     * (bot nghỉ nhiều hơn, target selection chậm lại) — KHÔNG sửa reward.
     */
    public static void throttleIfNeeded(AutoFarmBot bot) {
        if (!atCeiling(bot)) {
            return;
        }
        bot.botNeeds.addNeed(BotNeeds.REST, 1.5);
        bot.botNeeds.addNeed(BotNeeds.EXPLORE, 0.5);
    }

    /** Log chuẩn theo yêu cầu 28. */
    public static void logProgression(AutoFarmBot bot) {
        int maxReal = playerRefLevel();
        System.out.println("[BOT-PROGRESSION] bot=" + bot.id + " name=" + bot.name
                + " player=" + maxReal + " bot=" + bot.level
                + " ceiling=" + ceilingLevel(bot)
                + " gap=" + gapFor(bot)
                + " powerRatio=" + BotConfig.POWER_MIN_RATIO + "-" + BotConfig.POWER_MAX_RATIO);
    }
}
