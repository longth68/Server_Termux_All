package Exe_Z.bot.ai;

import Exe_Z.bot.AutoFarmBot;
import Exe_Z.mob.Mob;
import Exe_Z.util.NinjaUtils;

/**
 * Port từ NRO VirtualCombat: tìm mục tiêu, đánh, rút lui, hồi phục.
 * Tái sử dụng logic attackMob() của AutoFarmBot qua public wrapper.
 */
public class BotCombat {

    public static Mob findTarget(AutoFarmBot bot) {
        if (bot == null) {
            return null;
        }
        // Ưu tiên cứu người chơi: mob gần người chơi thật
        Mob help = BotPerception.findMobAttackingRealPlayer(bot);
        if (help != null && bot.botProfile.helpfulness > 0.55f) {
            return help;
        }
        int range = bot.botProfile.attackRange > 0 ? bot.botProfile.attackRange : 600;
        // RISK_TAKER đánh xa hơn, CAUTIOUS đánh gần hơn
        if (bot.botProfile.riskTolerance > 0.7f) {
            range = Math.max(range, 600);
        }
        return BotPerception.findMobTarget(bot, range);
    }

    public static boolean attack(AutoFarmBot bot, Mob mob) {
        if (bot == null || mob == null || mob.isDead) {
            return false;
        }
        int d = NinjaUtils.getDistance(bot.x, bot.y, mob.x, mob.y);
        if (d <= 110) {
            bot.aiAttackMob(mob);
            bot.botNeeds.satisfy(BotNeeds.EXP, 0.15);
            bot.botNeeds.satisfy(BotNeeds.GOLD, 0.08);
            return true;
        }
        BotDecision.moveCloser(bot, mob);
        return true;
    }

    public static boolean shouldRetreat(AutoFarmBot bot) {
        if (bot == null) {
            return false;
        }
        try {
            double hpRate = (double) bot.hp / Math.max(1, bot.maxHP);
            if (hpRate < 0.25) {
                return true;
            }
            // CAUTIOUS rút lui sớm hơn
            if (bot.botProfile.personalities.contains(BotPersonality.CAUTIOUS) && hpRate < 0.4) {
                return true;
            }
        } catch (Exception ignored) {
        }
        return false;
    }

    public static void heal(AutoFarmBot bot) {
        if (bot == null || bot.zone == null) {
            return;
        }
        try {
            bot.hp = bot.maxHP;
            bot.mp = bot.maxMP;
            bot.zone.getService().loadHP(bot);
            bot.botNeeds.satisfy(BotNeeds.REST, 10.0);
        } catch (Exception ignored) {
        }
    }
}
