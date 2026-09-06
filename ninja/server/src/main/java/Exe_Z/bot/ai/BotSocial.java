package Exe_Z.bot.ai;

import Exe_Z.bot.AutoFarmBot;
import Exe_Z.model.Char;
import Exe_Z.util.Log;
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
        // KẾT BẠN chủ động khi quan hệ đủ tốt mà chưa là bạn (mẫu NRO addFriend 2 chiều)
        try {
            if (bot.friends != null && bot.friends.get(player.name) == null
                    && bot.botMemory.relation(player.name) >= 8) {
                Exe_Z.model.Friend mine = bot.friends.get(player.name);
                Exe_Z.model.Friend theirs = (player.friends != null) ? player.friends.get(bot.name) : null;
                if (theirs != null) {
                    // Đối phương đã mời trước -> kết bạn 2 chiều luôn
                    bot.friends.put(player.name, new Exe_Z.model.Friend(player.name, (byte) 1));
                    theirs.type = 1;
                    bot.getService().addFriend(player.name, 1);
                    player.getService().addFriend(bot.name, 1);
                    BotChat.greetNewFriend(bot, player.name);
                } else {
                    // Bot gửi lời mời kết bạn
                    bot.friends.put(player.name, new Exe_Z.model.Friend(player.name, (byte) 0));
                    bot.getService().addFriend(player.name, 0);
                    player.getService().inviteFriend(bot.name);
                    bot.zone.getService().chat(bot.id, "Kết bạn với mình nha, cùng train cho vui!");
                }
                bot.botNeeds.satisfy(BotNeeds.SOCIAL, 1.0);
            }
        } catch (Exception e) {
            Log.error("BotSocial addFriend err: " + e.getMessage(), e);
        }
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
