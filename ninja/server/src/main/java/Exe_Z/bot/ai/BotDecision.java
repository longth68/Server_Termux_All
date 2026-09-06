package Exe_Z.bot.ai;

import Exe_Z.bot.AutoFarmBot;
import Exe_Z.mob.Mob;
import Exe_Z.util.NinjaUtils;
import java.util.LinkedHashMap;
import java.util.Map;

/**
 * UtilityEngine / DecisionEngine (yêu cầu 10): mỗi action có score từ Needs
 * + Perception + Progression; chọn action score cao nhất. Log [BOT-AI].
 * BotProgressionController throttle khi đạt ceiling (yêu cầu 13).
 */
public class BotDecision {

    /** ActionScore: tên action -> score hiện tính được. */
    public static Map<String, Double> lastScores = new LinkedHashMap<>();

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
        // Yêu cầu 13: đạt ceiling progression -> giảm farm, tăng nghỉ/khám phá
        try {
            BotProgressionController.throttleIfNeeded(bot);
        } catch (Exception ignored) {
        }

        // ===== Utility scores =====
        Map<String, Double> s = new LinkedHashMap<>();
        double rest = bot.botNeeds.get(BotNeeds.REST);
        double social = bot.botNeeds.get(BotNeeds.SOCIAL);
        double explore = bot.botNeeds.get(BotNeeds.EXPLORE);
        double item = bot.botNeeds.get(BotNeeds.ITEM);
        double gold = bot.botNeeds.get(BotNeeds.GOLD);
        double exp = bot.botNeeds.get(BotNeeds.EXP);

        s.put("HEAL", rest * 1.2);
        s.put("PICK_ITEM", (BotPerception.findNearItem(bot, 200) != null ? 2.0 : 0) + item * 0.6 + gold * 0.3);
        s.put("FIND_MOB", (mob != null && !mob.isDead ? 2.5 : 0) + exp * 0.8 + gold * 0.2);
        s.put("CHANGE_MAP", explore * 0.9 + (mob == null ? 0.8 : 0));
        s.put("PARTY", social * 0.8);
        s.put("CHAT", social * 0.9);
        s.put("IDLE", 0.2 + (bot.botProfile.laziness * 0.6));

        String bestAction = "IDLE";
        double bestScore = -1;
        for (Map.Entry<String, Double> e : s.entrySet()) {
            if (e.getValue() > bestScore) {
                bestScore = e.getValue();
                bestAction = e.getKey();
            }
        }
        // Giữ tương thích: lưu score cuối để debug/web
        lastScores = s;
        try {
            System.out.println("[BOT-AI] bot=" + bot.id + " action=" + bestAction
                    + " score=" + String.format("%.2f", bestScore)
                    + " state=" + bot.botState);
        } catch (Exception ignored) {
        }

        switch (bestAction) {
            case "HEAL":
                return BotGoals.ShortTerm.HEAL;
            case "PICK_ITEM":
                return BotGoals.ShortTerm.PICK_ITEM;
            case "FIND_MOB":
                return BotGoals.ShortTerm.FIND_MOB;
            case "CHANGE_MAP":
                return BotGoals.ShortTerm.CHANGE_MAP;
            case "PARTY":
                return BotGoals.ShortTerm.PARTY;
            case "CHAT":
                return BotGoals.ShortTerm.CHAT;
            default:
                return BotGoals.ShortTerm.IDLE;
        }
    }

    public static void moveCloser(AutoFarmBot bot, Mob mob) {
        if (bot != null && mob != null) {
            bot.aiMoveTo(mob.x, mob.y);
        }
    }
}
