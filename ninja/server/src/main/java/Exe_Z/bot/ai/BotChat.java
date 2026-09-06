package Exe_Z.bot.ai;

import Exe_Z.bot.AutoFarmBot;
import Exe_Z.model.Char;
import Exe_Z.util.Log;
import Exe_Z.util.NinjaUtils;
import java.util.Arrays;
import java.util.List;

/**
 * Port từ NRO VirtualChat: chat theo personality + relation + state.
 */
public class BotChat {

    private static final List<String> ROAM = Arrays.asList(
        "Ai train chung khong?", "Map nay kha on do",
        "Khu nay dong vui ghe", "Minh moi qua map nay",
        "Di mot minh hoi buon", "Map nay de train ky nang ne");
    private static final List<String> HUNT = Arrays.asList(
        "Quai gan day hoi trau", "Dame minh tam on roi",
        "Danh bai nay exp kha on", "De minh lo con nay");
    private static final List<String> REST = Arrays.asList(
        "Doi chut hoi mp", "Het mp roi nghi ti",
        "Nghi 1 chut roi danh tiep", "Cho minh hoi skill xong da");
    private static final List<String> SOCIAL = Arrays.asList(
        "Ai lap team train khong?", "Cho minh vao nhom voi",
        "Pt 2 nguoi clear nhanh hon do", "Team nao thieu nguoi khong?");
    private static final List<String> GREET_FRIEND = Arrays.asList(
        "Ong lai gap roi, train chung nhe!", "Hello ban, bai nay exp ngon lam");
    private static final List<String> GREET_STRANGER = Arrays.asList(
        "Chao ong, minh moi qua map nay", "Hi, train chung cho vui nhe?");

    public static void tick(AutoFarmBot bot) {
        if (bot == null || bot.zone == null) {
            return;
        }
        long now = System.currentTimeMillis();
        if (now < bot.nextAiChatTime) {
            return;
        }
        float rate = BotConfig.CHAT_RATE * bot.botProfile.talkativeness;
        if (rate <= 0.05f) {
            return;
        }
        bot.nextAiChatTime = now + (long) (NinjaUtils.nextInt(5000, 12000) / Math.max(0.2, rate));
        // Ưu tiên câu tùy chỉnh từ bot_chat.txt (mẫu Anwin VirtualChatConfig)
        try {
            BotChatConfig cfg = BotChatConfig.gI();
            cfg.reloadIfStale();
            String custom = cfg.randomLine();
            if (custom != null && !bot.botMemory.saidRecently(custom)
                    && NinjaUtils.nextInt(0, 100) < 60 * Math.min(1.0, rate)) {
                bot.zone.getService().chat(bot.id, custom);
                bot.botMemory.rememberChat(custom);
                bot.botNeeds.satisfy(BotNeeds.SOCIAL, 0.4);
                return;
            }
        } catch (Exception ignored) {
        }
        String line;
        Char near = BotPerception.nearestRealPlayer(bot, 400);
        if (near != null) {
            String label = bot.botMemory.relationLabel(near.name);
            if ("friend".equals(label)) {
                line = bot.botMemory.pickChat(GREET_FRIEND);
            } else {
                line = bot.botMemory.pickChat(GREET_STRANGER);
            }
            bot.botMemory.adjustRelation(near.name, 2);
        } else if (bot.hp < bot.maxHP * 0.35) {
            line = bot.botMemory.pickChat(REST);
        } else if (bot.botState == BotState.ATTACK || bot.botState == BotState.MOVE_TO_TARGET) {
            line = bot.botMemory.pickChat(HUNT);
        } else if (bot.botState == BotState.SOCIAL) {
            line = bot.botMemory.pickChat(SOCIAL);
        } else {
            line = bot.botMemory.pickChat(ROAM);
        }
        if (line == null || bot.botMemory.saidRecently(line)) {
            return;
        }
        try {
            bot.zone.getService().chat(bot.id, line);
            bot.botMemory.rememberChat(line);
            bot.botNeeds.satisfy(BotNeeds.SOCIAL, 0.4);
        } catch (Exception ex) {
            Log.error("BotChat err: " + ex.getMessage(), ex);
        }
    }
}
