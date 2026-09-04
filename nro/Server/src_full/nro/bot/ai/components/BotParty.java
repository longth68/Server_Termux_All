package nro.bot.ai.components;

import nro.bot.Bot;
import nro.bot.ai.BotBrain;
import nro.player.Player;
import Utils.Util;

public class BotParty {
    private Bot bot;
    private BotBrain brain;
    
    private Player leader;

    public BotParty(Bot bot, BotBrain brain) {
        this.bot = bot;
        this.brain = brain;
    }

    public void update() {
        if (bot.zone == null || bot.isDie()) return;

        // Nếu có tính cách xã hội, lâu lâu chào hỏi người chơi xung quanh thay vì bám đuôi
        if (brain.getProfile().personality == nro.bot.ai.BotProfile.Personality.SOCIAL) {
            if (Util.isTrue(1, 1000)) {
                for (Player pl : bot.zone.getPlayers()) {
                    if (pl != null && !pl.isBot && !pl.isDeTu && !pl.isBoss && Util.getDistance(bot, pl) < 150) {
                        nro.services.Service.getInstance().chat(bot, "Chào bạn " + pl.name + " nha!");
                        break;
                    }
                }
            }
        }
    }
}
