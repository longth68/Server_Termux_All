package nro.bot.ai.components;

import nro.bot.Bot;
import nro.bot.ai.BotBrain;
import nro.services.Service;
import Utils.Util;
import nro.player.Player;

public class BotChat {
    private Bot bot;
    private BotBrain brain;
    private long lastTimeChat;

    private static final String[] GREETINGS = {
        "Chào mọi người nhé",
        "Có ai pt không ạ?",
        "Hi ae",
        "Đang làm nhiệm vụ mệt quá",
        "Có ai kéo tui với",
        "Lag quá ae ơi"
    };

    public BotChat(Bot bot, BotBrain brain) {
        this.bot = bot;
        this.brain = brain;
    }

    public void updateChat() {
        if (bot.zone == null || bot.isDie()) return;

        // Chỉ chat nếu xung quanh có người chơi thật (tránh spam khi không có ai)
        boolean hasRealPlayer = false;
        for (Player pl : bot.zone.getPlayers()) {
            if (pl != null && !pl.isBot && !pl.isBoss && !pl.isDeTu) {
                hasRealPlayer = true;
                break;
            }
        }

        if (hasRealPlayer) {
            // Chat ngẫu nhiên sau khoảng 30 - 60 giây
            if (System.currentTimeMillis() - lastTimeChat > Util.nextInt(30000, 60000)) {
                if (Util.isTrue(30, 100)) { // 30% tỷ lệ nói chuyện
                    String msg = GREETINGS[Util.nextInt(0, GREETINGS.length - 1)];
                    Service.getInstance().chat(bot, msg);
                }
                lastTimeChat = System.currentTimeMillis();
            }
        }
    }
}
