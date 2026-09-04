package nro.bot.ai.components;

import nro.bot.Bot;
import nro.bot.ai.BotBrain;
import nro.bot.ai.BotState;
import Utils.Util;

public class BotDecision {
    private Bot bot;
    private BotBrain brain;
    private BotPerception perception;

    public BotDecision(Bot bot, BotBrain brain, BotPerception perception) {
        this.bot = bot;
        this.brain = brain;
        this.perception = perception;
    }

    public BotState evaluateNextState() {
        if (bot.isDie()) return BotState.DEAD;
        
        long hpPercent = (bot.nPoint.hp * 100) / bot.nPoint.hpMax;

        // Needs (Nhu cầu)
        int healScore = (hpPercent < 20) ? 90 : 0;
        int farmScore = brain.getProfile().isAggressive ? 60 : 30;
        int exploreScore = brain.getProfile().personality == nro.bot.ai.BotProfile.Personality.EXPLORER ? 80 : 20;
        
        int pickItemScore = 0;
        if (brain.getProfile().personality == nro.bot.ai.BotProfile.Personality.FARMER) {
            if (bot.zone != null && !bot.zone.items.isEmpty()) {
                pickItemScore = 70; // Ưu tiên nhặt đồ nếu là Farmer và có đồ xung quanh
            }
        }
        
        // Player Protection: Không cho phép Bot có sức mạnh quá cao để vượt mặt Player thật
        if (bot.nPoint != null && bot.nPoint.power > 5_000_000_000L) { // Tạm set mốc 5 Tỷ
            farmScore = 0; 
            exploreScore = 100; // Bắt buộc đi lang thang, không đánh quái nữa
        }

        // Nếu khu vực quá đông người chơi thật, Bot sẽ có xu hướng đi lang thang hoặc chuyển khu
        if (perception.realPlayersNearby.size() > 5 && brain.getProfile().personality != nro.bot.ai.BotProfile.Personality.SOCIAL) {
            farmScore -= 20; 
            exploreScore += 40;
        }

        // Chọn hành động dựa trên điểm số
        if (healScore >= 90) return BotState.HEAL;
        
        if (pickItemScore > farmScore && pickItemScore > exploreScore) {
            return BotState.PICK_ITEM;
        }
        
        if (exploreScore > farmScore) {
            return BotState.IDLE; // IDLE sẽ trigger wander
        } else {
            return BotState.FIND_TARGET;
        }
    }
}
