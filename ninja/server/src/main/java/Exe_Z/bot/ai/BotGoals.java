package Exe_Z.bot.ai;

import Exe_Z.util.NinjaUtils;

/**
 * Port từ NRO VirtualGoals: mục tiêu dài hạn + ngắn hạn.
 */
public class BotGoals {

    public enum LongTerm {
        FARM,
        QUEST,
        EXPLORE,
        SOCIAL,
        TRADE,
        REST
    }

    public enum ShortTerm {
        FIND_MOB,
        PICK_ITEM,
        CHAT,
        PARTY,
        CHANGE_MAP,
        HEAL,
        IDLE
    }

    public LongTerm longTerm = LongTerm.FARM;
    public ShortTerm shortTerm = ShortTerm.FIND_MOB;

    public void rollLongTerm(BotProfile profile) {
        int r = NinjaUtils.nextInt(0, 100);
        if (profile.personalities.contains(BotPersonality.QUESTER) && r < 25) {
            longTerm = LongTerm.QUEST;
        } else if (profile.personalities.contains(BotPersonality.EXPLORER) && r < 45) {
            longTerm = LongTerm.EXPLORE;
        } else if (profile.personalities.contains(BotPersonality.SOCIAL) && r < 60) {
            longTerm = LongTerm.SOCIAL;
        } else if (profile.personalities.contains(BotPersonality.TRADER) && r < 70) {
            longTerm = LongTerm.TRADE;
        } else if (profile.personalities.contains(BotPersonality.LAZY) && r < 78) {
            longTerm = LongTerm.REST;
        } else {
            longTerm = LongTerm.FARM;
        }
    }
}
