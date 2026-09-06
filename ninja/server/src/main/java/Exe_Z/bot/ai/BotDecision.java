package Exe_Z.bot.ai;

import Exe_Z.bot.AutoFarmBot;
import Exe_Z.mob.Mob;

/**
 * Port từ NRO VirtualDecision: chọn ShortTerm từ topNeed + cảm nhận.
 */
public class BotDecision {

    public static BotGoals.ShortTerm choose(AutoFarmBot bot, Mob mob) {
        if (bot == null) {
            return BotGoals.ShortTerm.IDLE;
        }
        if (bot.isDead) {
            return BotGoals.ShortTerm.IDLE;
        }
        bot.botNeeds.grow();
        // HP/MP thấp -> bơm REST
        try {
            if (bot.hp < bot.maxHP * 0.35 || bot.mp < bot.maxMP * 0.2) {
                bot.botNeeds.addNeed(BotNeeds.REST, 5.0);
            }
        } catch (Exception ignored) {
        }
        String top = bot.botNeeds.topNeed();
        switch (top) {
            case BotNeeds.REST:
                return BotGoals.ShortTerm.HEAL;
            case BotNeeds.SOCIAL:
                return BotGoals.ShortTerm.PARTY;
            case BotNeeds.EXPLORE:
                return BotGoals.ShortTerm.CHANGE_MAP;
            case BotNeeds.ITEM:
            case BotNeeds.GOLD:
                if (BotPerception.findNearItem(bot, 200) != null) {
                    return BotGoals.ShortTerm.PICK_ITEM;
                }
                break;
            default:
                break;
        }
        if (mob != null && !mob.isDead) {
            return BotGoals.ShortTerm.FIND_MOB;
        }
        if (bot.botNeeds.get(BotNeeds.SOCIAL) > 3.0) {
            return BotGoals.ShortTerm.CHAT;
        }
        return BotGoals.ShortTerm.IDLE;
    }

    public static void moveCloser(AutoFarmBot bot, Mob mob) {
        if (bot != null && mob != null) {
            bot.aiMoveTo(mob.x, mob.y);
        }
    }
}
