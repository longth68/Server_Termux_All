package nro.virtualplayer.core;

import java.util.ArrayList;
import java.util.List;
import java.util.Random;

/**
 * Hồ sơ & danh tính của Virtual Player.
 * PHASE 2 - Virtual Player Core.
 * Gồm: danh tính (tên, giới tính, cấp độ), personality (đa trọng số),
 * tốc độ phản ứng, lịch chơi, mức rủi ro, tần suất chat, sở thích party.
 */
public class VirtualProfile {

    // ===== IDENTITY =====
    public String name;
    public byte gender;
    public int level;
    public String title;

    // ===== PERSONALITY (đa trọng số) =====
    private final List<VirtualPersonality> personalities = new ArrayList<>();
    private float talkativeness = 0.4f;   // 0-1: tần suất chat
    private float riskTolerance = 0.5f;   // 0-1: mức liều
    private float helpfulness = 0.3f;     // 0-1: giúp đỡ
    private float competitiveness = 0.5f; // 0-1: cạnh tranh
    private float laziness = 0.3f;        // 0-1: lười
    private float greed = 0.4f;           // 0-1: giữ vàng/item

    // ===== PREFERENCE / SCHEDULE =====
    public float partyPreference = 0.3f;  // 0-1
    public float exploreRate = 0.3f;      // 0-1
    public float socialRate = 0.3f;       // 0-1
    public float afkRate = 0.2f;          // 0-1

    // ===== REACTION / TIMING =====
    public long reactionDelay;    // ms, phản ứng
    public long thinkDelay;       // ms, suy nghĩ
    public int attackRange;       // px

    // ===== ONLINE SCHEDULE =====
    public long onlineDuration;   // ms
    public long offlineDuration;  // ms

    // ===== CATCH-UP (Bot chậm hơn Player) =====
    public float catchupPercent;  // 0.40 - 0.97
    public float activityRate;    // 0-1

    private final Random random = new Random();

    public VirtualProfile(String name) {
        this.name = name;
        rollPersonalities();
        rollTimings();
        rollSchedule();
        rollCatchup();
    }

    private void rollPersonalities() {
        // Mỗi bot có 1-4 tính cách chính
        int count = 1 + random.nextInt(4);
        List<VirtualPersonality> all = new ArrayList<>(List.of(VirtualPersonality.values()));
        // Loại bỏ các tính cách mâu thuẫn để giữ hợp lý
        for (int i = 0; i < count && !all.isEmpty(); i++) {
            int idx = random.nextInt(all.size());
            VirtualPersonality p = all.remove(idx);
            personalities.add(p);
            applyTrait(p);
        }
    }

    private void applyTrait(VirtualPersonality p) {
        switch (p) {
            case QUIET: talkativeness = 0.15f; break;
            case TALKATIVE: talkativeness = 0.7f; break;
            case SOCIAL: talkativeness = 0.6f; partyPreference = 0.7f; socialRate = 0.7f; helpfulness = 0.6f; break;
            case SOLO: partyPreference = 0.05f; socialRate = 0.05f; break;
            case CAUTIOUS: riskTolerance = 0.2f; break;
            case RISK_TAKER: riskTolerance = 0.85f; break;
            case GREEDY: greed = 0.85f; break;
            case LAZY: laziness = 0.8f; afkRate = 0.6f; break;
            case HARDCORE: activityRate = 0.85f; catchupPercent = 0.85f; break;
            case HELPFUL: helpfulness = 0.9f; break;
            case COMPETITIVE: competitiveness = 0.9f; break;
            case EXPLORER: exploreRate = 0.8f; break;
            case FARMER: exploreRate = 0.15f; break;
            default: break;
        }
    }

    private void rollTimings() {
        // Veteran/Competitive phản ứng nhanh hơn
        float base = 0.6f + random.nextFloat() * 0.5f;
        reactionDelay = (long) (600 + base * 1400 + random.nextInt(300));
        thinkDelay = (long) (900 + random.nextInt(800));
        attackRange = 50 + random.nextInt(60);
    }

    private void rollSchedule() {
        onlineDuration = 30 * 60 * 1000L + random.nextInt(2 * 60 * 60 * 1000); // 30 phút - 2.5h
        offlineDuration = 5 * 60 * 1000L + random.nextInt(40 * 60 * 1000);      // 5-45 phút
        // afk_rate config: 0 = hiếm khi AFK (online dài, offline ngắn), 1 = hay AFK (ngược lại)
        try {
            float afk = nro.virtualplayer.VirtualConfig.gI().afkRate;
            if (afk > 0) {
                onlineDuration = (long) (onlineDuration / (0.5f + afk));
                offlineDuration = (long) (offlineDuration * (0.5f + afk));
            }
        } catch (Exception ignored) {}
    }

    private void rollCatchup() {
        // Bot cày chậm hơn Player: 40%-90%
        catchupPercent = 0.40f + random.nextFloat() * 0.50f;
        activityRate = 0.5f + random.nextFloat() * 0.4f;
    }

    public boolean hasPersonality(VirtualPersonality p) {
        return personalities.contains(p);
    }

    /**
     * Khôi phục personality + trait từ dữ liệu lưu (persistence).
     */
    public void restoreFromSave(java.util.List<String> persNames,
                                Float talk, Float risk, Float help, Float comp, Float lazy, Float greed) {
        if (persNames != null && !persNames.isEmpty()) {
            personalities.clear();
            for (String s : persNames) {
                try {
                    VirtualPersonality p = VirtualPersonality.valueOf(s);
                    if (!personalities.contains(p)) {
                        personalities.add(p);
                        applyTrait(p);
                    }
                } catch (Exception ignored) {}
            }
        }
        if (talk != null) talkativeness = clamp01(talk);
        if (risk != null) riskTolerance = clamp01(risk);
        if (help != null) helpfulness = clamp01(help);
        if (comp != null) competitiveness = clamp01(comp);
        if (lazy != null) laziness = clamp01(lazy);
        if (greed != null) greed = clamp01(greed);
    }

    private static float clamp01(float v) {
        return Math.max(0f, Math.min(1f, v));
    }

    public List<VirtualPersonality> getPersonalities() {
        return personalities;
    }

    public float getTalkativeness() { return talkativeness; }
    public float getRiskTolerance() { return riskTolerance; }
    public float getHelpfulness() { return helpfulness; }
    public float getCompetitiveness() { return competitiveness; }
    public float getLaziness() { return laziness; }
    public float getGreed() { return greed; }

    public void setName(String name) { this.name = name; }
    public String getName() { return name; }

    // Tiện ích: quyết định theo xác suất dựa trên trait
    public boolean decide(float probability) {
        return random.nextFloat() < probability;
    }

    public String describe() {
        return name + " [" + personalities + "]";
    }

    public static String randomName() {
        String[] names = {
            "ThiênMệnh", "LongKiếm", "MộcLan", "TiểuPhong", "VôDanh", "KiếmKhách",
            "HànPhong", "ThanhVân", "BạchHổ", "HoàngLong", "TửYên", "NgânHà",
            "KimLong", "LưuVân", "SơnHà", "CựThần", "PhongHỏa", "LôiĐình",
            "ThiênHà", "ĐịaHổ", "HuyềnVũ", "ChuTước", "ThanhLong", "BạchHổ2",
            "DạMinh", "HồiPhong", "ThiếtKiếm", "BăngSơn", "HỏaDiệm", "SinhMệnh"
        };
        return names[new Random().nextInt(names.length)] + (1 + new Random().nextInt(99));
    }
}
