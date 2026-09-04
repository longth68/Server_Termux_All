package nro.virtualplayer.core;

/**
 * Hệ thống nhu cầu của Virtual Player.
 * PHASE 2 - Virtual Player Core.
 * Các nhu cầu dao động theo thời gian và trạng thái, ảnh hưởng quyết định.
 */
public class VirtualNeeds {

    // Nhu cầu 0-100
    private float hpNeed = 50;
    private float mpNeed = 50;
    private float expNeed = 40;
    private float goldNeed = 40;
    private float itemNeed = 30;
    private float questNeed = 30;
    private float socialNeed = 30;
    private float restNeed = 0;
    private float safetyNeed = 20;
    private float exploreNeed = 20;

    public void update() {
        // Nhu cầu tăng dần theo thời gian nếu không được đáp ứng
        expNeed = Math.min(100, expNeed + 0.02f);
        goldNeed = Math.min(100, goldNeed + 0.015f);
        itemNeed = Math.min(100, itemNeed + 0.01f);
        questNeed = Math.min(100, questNeed + 0.012f);
        socialNeed = Math.min(100, socialNeed + 0.008f);
        restNeed = Math.min(100, restNeed + 0.01f);
        exploreNeed = Math.min(100, exploreNeed + 0.005f);
        safetyNeed = Math.max(0, safetyNeed - 0.01f);
    }

    public void satisfyRest() { restNeed = Math.max(0, restNeed - 40); }
    public void satisfySocial() { socialNeed = Math.max(0, socialNeed - 35); }
    public void satisfyGold(float amt) { goldNeed = Math.max(0, goldNeed - amt); }
    public void satisfyExp(float amt) { expNeed = Math.max(0, expNeed - amt); }
    public void satisfyQuest() { questNeed = Math.max(0, questNeed - 40); }
    public void satisfyItem(float amt) { itemNeed = Math.max(0, itemNeed - amt); }
    public void satisfyExplore() { exploreNeed = Math.max(0, exploreNeed - 30); }
    public void raiseSafety(float amt) { safetyNeed = Math.min(100, safetyNeed + amt); }
    public void setHpNeed(float v) { hpNeed = v; }
    public void setMpNeed(float v) { mpNeed = v; }

    public float getHpNeed() { return hpNeed; }
    public float getMpNeed() { return mpNeed; }
    public float getExpNeed() { return expNeed; }
    public float getGoldNeed() { return goldNeed; }
    public float getItemNeed() { return itemNeed; }
    public float getQuestNeed() { return questNeed; }
    public float getSocialNeed() { return socialNeed; }
    public float getRestNeed() { return restNeed; }
    public float getSafetyNeed() { return safetyNeed; }
    public float getExploreNeed() { return exploreNeed; }
}
