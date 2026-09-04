package nro.bot.ai;

import Utils.Util;
import java.util.ArrayList;
import java.util.List;

public class BotProfile {
    // Thông tin nhận diện cơ bản
    public String identityName;
    
    // Personality Type
    public enum Personality {
        FARMER, EXPLORER, SOCIAL, HARDCORE, TRADER, CASUAL
    }
    public Personality personality;
    
    // Thuộc tính hành vi
    public boolean isAggressive;
    public boolean isGenerous;
    public boolean isTalkative;
    public int riskTolerance; // 0-100: Mức độ liều lĩnh (VD: đánh boss, đánh quái cấp cao)

    // Khoảng cách và kỹ năng
    public int maxWanderDistance;
    public int attackRange;
    
    // Lịch trình (Schedule)
    public long loginTime;
    public long onlineDuration;
    public long offlineDuration;
    
    // Độ trễ phản xạ (Human Imperfection)
    public int reactionDelay;
    public int thinkDelay;

    public BotProfile(String name) {
        this.identityName = name;
        randomizePersonality();
    }

    private void randomizePersonality() {
        Personality[] values = Personality.values();
        this.personality = values[Util.nextInt(0, values.length - 1)];

        // Random cơ bản
        this.isAggressive = Util.isTrue(50, 100);
        this.isGenerous = Util.isTrue(30, 100);
        this.isTalkative = Util.isTrue(40, 100);
        this.riskTolerance = Util.nextInt(10, 90);
        this.maxWanderDistance = Util.nextInt(200, 500);
        this.attackRange = Util.nextInt(50, 150);

        // Áp dụng trọng số theo Personality
        switch (this.personality) {
            case FARMER:
                this.isAggressive = true;
                this.riskTolerance = 30; // Farm an toàn
                this.isTalkative = false;
                break;
            case HARDCORE:
                this.isAggressive = true;
                this.riskTolerance = 95; // Liều lĩnh
                this.thinkDelay = Util.nextInt(300, 800); // Suy nghĩ nhanh
                break;
            case SOCIAL:
                this.isTalkative = true;
                this.isGenerous = true;
                this.riskTolerance = 20;
                break;
            case TRADER:
                this.isGenerous = false; // Tham lam
                this.isTalkative = true;
                break;
            case EXPLORER:
                this.maxWanderDistance = Util.nextInt(600, 1200); // Chạy lung tung
                this.isAggressive = false;
                break;
            case CASUAL:
            default:
                break;
        }

        // Lịch trình Online
        this.loginTime = System.currentTimeMillis();
        if (this.personality == Personality.HARDCORE) {
            this.onlineDuration = Util.nextInt(180, 400) * 60 * 1000L; // 3-6 tiếng
            this.offlineDuration = Util.nextInt(30, 60) * 60 * 1000L;
        } else {
            this.onlineDuration = Util.nextInt(30, 120) * 60 * 1000L; // 30p-2 tiếng
            this.offlineDuration = Util.nextInt(120, 240) * 60 * 1000L;
        }

        // Phản xạ
        if (this.thinkDelay == 0) {
            this.thinkDelay = Util.nextInt(1000, 3000);
        }
        this.reactionDelay = Util.nextInt(200, 1000);
    }
}
