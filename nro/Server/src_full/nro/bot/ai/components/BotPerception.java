package nro.bot.ai.components;

import nro.bot.Bot;
import nro.bot.ai.BotBrain;
import nro.player.Player;
import nro.mob.Mob;
import java.util.ArrayList;
import java.util.List;

public class BotPerception {
    private Bot bot;
    private BotBrain brain;
    
    public List<Player> realPlayersNearby = new ArrayList<>();
    public List<Mob> mobsNearby = new ArrayList<>();
    public Player nearestPlayer = null;

    public BotPerception(Bot bot, BotBrain brain) {
        this.bot = bot;
        this.brain = brain;
    }

    public void scan() {
        if (bot.zone == null) return;
        
        realPlayersNearby.clear();
        mobsNearby.clear();
        nearestPlayer = null;
        int minDistance = Integer.MAX_VALUE;

        // Quét Player
        for (Player pl : bot.zone.getPlayers()) {
            if (pl != null && !pl.isBot && !pl.isBoss && !pl.isDeTu && pl.id != bot.id) {
                realPlayersNearby.add(pl);
                int dist = Utils.Util.getDistance(bot, pl);
                if (dist < minDistance) {
                    minDistance = dist;
                    nearestPlayer = pl;
                }
            }
        }

        // Quét Quái
        for (Mob mob : bot.zone.mobs) {
            if (!mob.isDie()) {
                mobsNearby.add(mob);
            }
        }
    }
}
