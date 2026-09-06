package Exe_Z.bot.ai;

import Exe_Z.bot.AutoFarmBot;

/**
 * Port từ NRO VirtualEconomy: vàng/item cho BOT.
 * NSO BOT dùng User giả (gold=0) nên chỉ cộng Needs, không đụng DB.
 */
public class BotEconomy {

    public static void addGold(AutoFarmBot bot, long amount) {
        if (bot == null) {
            return;
        }
        try {
            long add = (long) (amount * BotConfig.GOLD_RATE);
            if (bot.user != null) {
                bot.user.gold += add;
            }
            bot.botNeeds.satisfy(BotNeeds.GOLD, 0.5);
        } catch (Exception ignored) {
        }
    }

    public static boolean isBagNearlyFull(AutoFarmBot bot) {
        if (bot == null || bot.bag == null) {
            return true;
        }
        int used = 0;
        for (Object it : bot.bag) {
            if (it != null) {
                used++;
            }
        }
        return used >= bot.bag.length * 0.9;
    }
}
