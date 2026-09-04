package nro.virtualplayer;

import nro.virtualplayer.core.VirtualGoals;
import nro.virtualplayer.core.VirtualPersonality;
import nro.virtualplayer.core.VirtualState;
import java.util.Random;

/**
 * Utility AI Decision Engine cho Virtual Player.
 * PHASE 3 - Decision.
 * Mỗi hành động có điểm score dựa trên: Perception + Needs + Goals + Personality.
 * Bot KHÔNG luôn chọn điểm cao nhất - dùng xác suất theo trọng số để tránh pattern máy móc.
 */
public class VirtualDecision {

    private final VirtualPlayer vp;
    private final VirtualPerception perception;
    private final Random random = new Random();

    public enum Action {
        FARM, QUEST, EXPLORE, REST, GO_SHOP, SOCIALIZE, PICK_ITEM, CHANGE_MAP, HUNT_BOSS, IDLE
    }

    public VirtualDecision(VirtualPlayer vp, VirtualPerception perception) {
        this.vp = vp;
        this.perception = perception;
    }

    /**
     * Tính điểm utility cho từng hành động dựa trên trạng thái hiện tại.
     */
    public float score(Action action) {
        var profile = vp.profile;
        var needs = vp.needs;
        var goals = vp.goals;
        float s = 0;

        switch (action) {
            case FARM:
                s += needs.getExpNeed() * 0.8f;
                s += needs.getGoldNeed() * 0.5f;
                s += profile.hasPersonality(VirtualPersonality.FARMER) ? 20 : 0;
                s += profile.hasPersonality(VirtualPersonality.HARDCORE) ? 15 : 0;
                s += profile.hasPersonality(VirtualPersonality.LAZY) ? -15 : 0;
                // Nếu không có mob xung quanh thì giảm điểm
                if (perception.getNearbyMobs().isEmpty()) s -= 25;
                // Nếu zone quá đông -> giảm (tranh quái)
                if (perception.isZoneCrowded(4)) s -= 15;
                break;
            case QUEST:
                s += needs.getQuestNeed() * 1.2f;
                s += profile.hasPersonality(VirtualPersonality.QUESTER) ? 25 : 0;
                if (goals.getLongTerm() == VirtualGoals.LongTermGoal.COMPLETE_QUESTS) s += 20;
                break;
            case EXPLORE:
                s += needs.getExploreNeed() * 1.1f;
                s += profile.hasPersonality(VirtualPersonality.EXPLORER) ? 25 : 0;
                if (goals.getLongTerm() == VirtualGoals.LongTermGoal.EXPLORE) s += 20;
                break;
            case REST:
                s += needs.getRestNeed() * 1.5f;
                s += profile.hasPersonality(VirtualPersonality.LAZY) ? 15 : 0;
                break;
            case GO_SHOP:
                s += needs.getItemNeed() * 0.9f;
                s += profile.hasPersonality(VirtualPersonality.TRADER) ? 20 : 0;
                // Nếu HP/MP thấp -> cần mua potion
                if (vp.nPoint != null && vp.nPoint.hp < vp.nPoint.hpMax * 0.5f) s += 15;
                break;
            case SOCIALIZE:
                s += needs.getSocialNeed() * 1.0f;
                s += profile.hasPersonality(VirtualPersonality.SOCIAL) ? 25 : 0;
                s += profile.hasPersonality(VirtualPersonality.QUIET) ? -20 : 0;
                if (perception.hasRealPlayerNearby()) s += 10;
                break;
            case PICK_ITEM:
                s += perception.getNearbyItems().isEmpty() ? 0 : 30;
                s += needs.getItemNeed() * 0.3f;
                break;
            case CHANGE_MAP:
                s += needs.getExploreNeed() * 0.5f;
                if (perception.isZoneCrowded(5)) s += 25; // đông quá -> đổi map
                s += profile.hasPersonality(VirtualPersonality.EXPLORER) ? 10 : 0;
                break;
            case HUNT_BOSS:
                s += profile.hasPersonality(VirtualPersonality.RISK_TAKER) ? 20 : 0;
                s += profile.hasPersonality(VirtualPersonality.COMPETITIVE) ? 10 : 0;
                s += profile.getRiskTolerance() * 20;
                break;
            case IDLE:
                s += profile.getLaziness() * 15;
                break;
        }

        // Mục tiêu dài hạn ảnh hưởng nhẹ
        switch (goals.getLongTerm()) {
            case LEVEL_UP: s += (action == Action.FARM) ? 5 : 0; break;
            case EARN_GOLD: s += (action == Action.FARM || action == Action.GO_SHOP) ? 5 : 0; break;
            case HUNT_RARE: s += (action == Action.FARM) ? 8 : 0; break;
            default: break;
        }

        return s;
    }

    /**
     * Chọn hành động theo utility + xác suất (tránh pattern máy móc).
     */
    public Action chooseAction() {
        Action[] actions = Action.values();
        float[] scores = new float[actions.length];
        float total = 0;
        for (int i = 0; i < actions.length; i++) {
            scores[i] = Math.max(0, score(actions[i]));
            total += scores[i];
        }

        // Không hành động nào nổi bật -> idle
        if (total <= 0.5f) return Action.IDLE;

        // Weighted random selection
        float roll = random.nextFloat() * total;
        float cumulative = 0;
        for (int i = 0; i < actions.length; i++) {
            cumulative += scores[i];
            if (roll <= cumulative) {
                return actions[i];
            }
        }
        return actions[actions.length - 1];
    }

    /**
     * Map Action -> VirtualState để Brain chuyển trạng thái.
     */
    public VirtualState toState(Action action) {
        switch (action) {
            case FARM: return VirtualState.FIND_TARGET;
            case QUEST: return VirtualState.DO_QUEST;
            case EXPLORE: return VirtualState.EXPLORE;
            case REST: return VirtualState.REST;
            case GO_SHOP: return VirtualState.GO_SHOP;
            case SOCIALIZE: return VirtualState.SOCIAL;
            case PICK_ITEM: return VirtualState.PICK_ITEM;
            case CHANGE_MAP: return VirtualState.CHANGE_MAP;
            case HUNT_BOSS: return VirtualState.FIND_TARGET;
            default: return VirtualState.IDLE;
        }
    }
}
