package nro.bot.ai.components;

import nro.bot.Bot;
import nro.bot.ai.BotBrain;
import nro.services.Fun.UseItem;
import models.Item.Item;
import models.Item.ItemService;
import Utils.Util;

public class BotInventory {
    private Bot bot;
    private BotBrain brain;
    private long lastTimeHeal;

    public BotInventory(Bot bot, BotBrain brain) {
        this.bot = bot;
        this.brain = brain;
    }

    public void checkAndHeal() {
        if (bot.isDie()) return;

        // Nếu HP dưới 20%
        long currentHp = bot.nPoint.hp;
        long maxHp = bot.nPoint.hpMax;
        
        if (currentHp < maxHp * 0.2) {
            if (System.currentTimeMillis() - lastTimeHeal > 5000) {
                // Tạm dùng 1 option đậu thần ngẫu nhiên như code cũ
                int[] buffPea = {1, 2, 3, 4, 5, 6, 7, 8, 9};
                int option = buffPea[Util.nextInt(0, buffPea.length - 1)];
                UseItem.gI().eatPeaBot(bot, option);
                lastTimeHeal = System.currentTimeMillis();
            }
        }
    }

    public boolean checkAndPickItem() {
        if (brain.getProfile().personality != nro.bot.ai.BotProfile.Personality.FARMER || bot.zone == null || bot.zone.items.isEmpty()) {
            return false;
        }

        // Tìm item trên đất
        for (nro.map.ItemMap item : bot.zone.items) {
            if (item != null && !item.isPickedUp) {
                // Chỉ nhặt đồ của mình hoặc đồ không chủ
                int playerId = Math.abs(item.playerId > 100_000_000 ? 1_000_000_000 - (int) item.playerId : (int) item.playerId);
                if (playerId == bot.id || item.playerId == bot.id || item.playerId == -1) {
                    
                    int distance = Util.getDistance(bot.location.x, bot.location.y, item.x, item.y);
                    
                    if (distance <= 50) {
                        // Đã tới gần, tiến hành nhặt
                        bot.zone.pickItem(bot, item.itemMapId);
                        return true;
                    } else if (distance <= 300) {
                        // Chưa tới gần, đi về phía item
                        int newY = bot.zone.map.yPhysicInTopBot(item.x, item.y);
                        bot.move(bot, item.x, newY);
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
