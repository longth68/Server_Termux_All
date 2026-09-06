package Exe_Z.bot.ai;

import Exe_Z.bot.AutoFarmBot;
import Exe_Z.model.Char;
import Exe_Z.util.NinjaUtils;

/**
 * Port từ NRO VirtualSocial: kết bạn, party, giúp đỡ.
 * Party/trade thực thi qua AutoFarmBot.aiTryParty() để tái dùng code cũ.
 */
public class BotSocial {

    public static void tick(AutoFarmBot bot) {
        if (bot == null) {
            return;
        }
        long now = System.currentTimeMillis();
        if (now < bot.nextAiSocialTime) {
            return;
        }
        bot.nextAiSocialTime = now + NinjaUtils.nextInt(15000, 30000);
        // SOLO ít social hơn, SOCIAL/HELPFUL nhiều hơn
        if (bot.botProfile.personalities.contains(BotPersonality.SOLO)
                && NinjaUtils.nextInt(0, 100) < 60) {
            return;
        }
        Char player = BotPerception.nearestRealPlayer(bot, 500);
        if (player == null) {
            return;
        }
        bot.botMemory.adjustRelation(player.name, 3);
        // Nhờ/gửi party qua logic cũ (đã kiểm tra level/village)
        try {
            bot.aiTryParty(player);
        } catch (Exception ignored) {
        }
        bot.botNeeds.satisfy(BotNeeds.SOCIAL, 1.0);
        if (bot.botProfile.helpfulness > 0.6f) {
            bot.botNeeds.satisfy(BotNeeds.QUEST, 0.3);
        }
    }
}
