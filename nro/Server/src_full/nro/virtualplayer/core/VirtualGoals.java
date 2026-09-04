package nro.virtualplayer.core;

import java.util.Random;

/**
 * Mục tiêu ngắn hạn & dài hạn của Virtual Player.
 * PHASE 2 - Virtual Player Core.
 * Mục tiêu dài hạn ảnh hưởng hành vi hàng ngày; ngắn hạn là hành động trước mắt.
 */
public class VirtualGoals {

    public enum LongTermGoal {
        LEVEL_UP, INCREASE_POWER, GET_EQUIPMENT, COMPLETE_QUESTS,
        EARN_GOLD, HUNT_RARE, EXPLORE, RANKING, RELATIONSHIP, FARM
    }

    public enum ShortTermGoal {
        KILL_MONSTERS, DO_QUEST, EARN_GOLD, BUY_POTION, GET_MATERIAL,
        CHANGE_MAP, FIND_PARTY, HUNT_BOSS, REST, EXPLORE, SOCIALIZE, SHOP
    }

    private LongTermGoal longTerm = LongTermGoal.LEVEL_UP;
    private ShortTermGoal shortTerm = ShortTermGoal.KILL_MONSTERS;
    private final Random random = new Random();

    public VirtualGoals() {
        rollLongTerm();
    }

    public void rollLongTerm() {
        LongTermGoal[] goals = LongTermGoal.values();
        longTerm = goals[random.nextInt(goals.length)];
    }

    public LongTermGoal getLongTerm() { return longTerm; }
    public ShortTermGoal getShortTerm() { return shortTerm; }

    public void setShortTerm(ShortTermGoal g) { this.shortTerm = g; }

    // Mục tiêu ngắn hạn ngẫu nhiên nhưng phù hợp personality
    public void rollShortTerm(VirtualProfile profile) {
        int r = random.nextInt(100);
        if (profile.hasPersonality(VirtualPersonality.QUESTER)) {
            shortTerm = (r < 60) ? ShortTermGoal.DO_QUEST : ShortTermGoal.KILL_MONSTERS;
        } else if (profile.hasPersonality(VirtualPersonality.FARMER)) {
            shortTerm = (r < 55) ? ShortTermGoal.KILL_MONSTERS : ShortTermGoal.EARN_GOLD;
        } else if (profile.hasPersonality(VirtualPersonality.TRADER)) {
            shortTerm = (r < 50) ? ShortTermGoal.SHOP : ShortTermGoal.BUY_POTION;
        } else if (profile.hasPersonality(VirtualPersonality.EXPLORER)) {
            shortTerm = (r < 60) ? ShortTermGoal.EXPLORE : ShortTermGoal.CHANGE_MAP;
        } else if (profile.hasPersonality(VirtualPersonality.SOCIAL)) {
            shortTerm = (r < 50) ? ShortTermGoal.SOCIALIZE : ShortTermGoal.FIND_PARTY;
        } else {
            // Mặc định: cân bằng
            if (r < 40) shortTerm = ShortTermGoal.KILL_MONSTERS;
            else if (r < 60) shortTerm = ShortTermGoal.EARN_GOLD;
            else if (r < 75) shortTerm = ShortTermGoal.DO_QUEST;
            else shortTerm = ShortTermGoal.CHANGE_MAP;
        }
    }
}
