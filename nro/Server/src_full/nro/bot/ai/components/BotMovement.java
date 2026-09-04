package nro.bot.ai.components;

import nro.bot.Bot;
import nro.bot.ai.BotBrain;
import Utils.Util;
import nro.player.Player;
import nro.mob.Mob;

public class BotMovement {
    private Bot bot;
    private BotBrain brain;

    public BotMovement(Bot bot, BotBrain brain) {
        this.bot = bot;
        this.brain = brain;
    }

    public void wander() {
        if (bot.zone == null) return;
        
        // Thỉnh thoảng đi loanh quanh trong phạm vi
        int range = brain.getProfile().maxWanderDistance;
        int newX = bot.location.x + Util.nextInt(-range, range);
        
        // Cản map
        if (newX < 50) newX = 50;
        if (newX > bot.zone.map.mapWidth - 50) newX = bot.zone.map.mapWidth - 50;
        
        int newY = bot.zone.map.yPhysicInTopBot(newX, bot.location.y);
        bot.move(bot, newX, newY);
    }

    public void moveToTarget(int targetX, int targetY) {
        if (bot.zone == null) return;
        
        int distance = Math.abs(bot.location.x - targetX);
        int direction = (bot.location.x < targetX) ? 1 : -1;
        
        int moveStep = (distance > 30) ? 40 : (Util.isTrue(20, 100) ? Util.nextInt(3, 5) : 0);
        int newX = bot.location.x + (direction * moveStep);
        
        // Ràng buộc bản đồ
        if (newX < 50) newX = 50;
        if (newX > bot.zone.map.mapWidth - 50) newX = bot.zone.map.mapWidth - 50;

        int newY = bot.zone.map.yPhysicInTopBot(newX, targetY);
        bot.move(bot, newX, newY);
    }
}
