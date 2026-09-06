package Exe_Z.bot.ai;

import Exe_Z.util.NinjaUtils;
import java.util.EnumSet;
import java.util.Set;
import org.json.simple.JSONObject;

/**
 * Port từ NRO VirtualProfile: hồ sơ tính cách + chỉ số hành vi.
 * rollPersonality() ngẫu nhiên, applyTraits() suy ra talk/risk/help/...
 */
public class BotProfile {

    public String nameSeed = "";
    public final Set<BotPersonality> personalities = EnumSet.noneOf(BotPersonality.class);

    public float talkativeness = 0.5f;
    public float riskTolerance = 0.5f;
    public float helpfulness = 0.5f;
    public float competitiveness = 0.5f;
    public float laziness = 0.3f;
    public float greed = 0.4f;

    public int reactionDelay = 900; // ms
    public int attackRange = 110;
    public long onlineDuration = 3600000L;
    public long offlineDuration = 1200000L;
    public float catchupPercent = 0.6f;

    public void rollPersonality() {
        personalities.clear();
        BotPersonality[] all = BotPersonality.values();
        int n = NinjaUtils.nextInt(3, 6);
        while (personalities.size() < n) {
            personalities.add(all[NinjaUtils.nextInt(0, all.length - 1)]);
        }
        applyTraits();
    }

    public void applyTraits() {
        talkativeness = traitsFrom(0.15f, 0.9f, BotPersonality.TALKATIVE, BotPersonality.SOCIAL);
        if (personalities.contains(BotPersonality.QUIET)) {
            talkativeness *= 0.3f;
        }
        riskTolerance = traitsFrom(0.15f, 0.9f, BotPersonality.RISK_TAKER, BotPersonality.HARDCORE, BotPersonality.PVP_PLAYER);
        if (personalities.contains(BotPersonality.CAUTIOUS)) {
            riskTolerance *= 0.5f;
        }
        helpfulness = traitsFrom(0.1f, 0.9f, BotPersonality.HELPFUL, BotPersonality.SOCIAL);
        competitiveness = traitsFrom(0.1f, 0.9f, BotPersonality.COMPETITIVE, BotPersonality.PVP_PLAYER);
        laziness = traitsFrom(0.1f, 0.85f, BotPersonality.LAZY, BotPersonality.CASUAL);
        greed = traitsFrom(0.1f, 0.9f, BotPersonality.GREEDY, BotPersonality.COLLECTOR, BotPersonality.TRADER);
        reactionDelay = personalities.contains(BotPersonality.HARDCORE) ? 500 : NinjaUtils.nextInt(700, 1700);
        attackRange = 110;
        catchupPercent = personalities.contains(BotPersonality.LAZY) ? 0.3f : 0.6f;
        if (personalities.contains(BotPersonality.VETERAN)) {
            catchupPercent = 0.75f;
        }
    }

    private float traitsFrom(float base, float per, BotPersonality... keys) {
        float v = base;
        for (BotPersonality k : keys) {
            if (personalities.contains(k)) {
                v += per / keys.length;
            }
        }
        return Math.max(0.05f, Math.min(1.0f, v));
    }

    @SuppressWarnings("unchecked")
    public JSONObject toSave() {
        JSONObject o = new JSONObject();
        StringBuilder sb = new StringBuilder();
        for (BotPersonality p : personalities) {
            if (sb.length() > 0) {
                sb.append(',');
            }
            sb.append(p.name());
        }
        o.put("personality", sb.toString());
        o.put("talk", talkativeness);
        o.put("risk", riskTolerance);
        o.put("help", helpfulness);
        o.put("comp", competitiveness);
        o.put("lazy", laziness);
        o.put("greed", greed);
        o.put("reaction", reactionDelay);
        o.put("online_duration", onlineDuration);
        o.put("offline_duration", offlineDuration);
        o.put("catchup", catchupPercent);
        return o;
    }

    public void restoreFromSave(JSONObject o) {
        if (o == null) {
            return;
        }
        try {
            personalities.clear();
            String s = String.valueOf(o.getOrDefault("personality", ""));
            for (String part : s.split(",")) {
                try {
                    personalities.add(BotPersonality.valueOf(part.trim()));
                } catch (Exception ignored) {
                }
            }
            if (personalities.isEmpty()) {
                rollPersonality();
                return;
            }
            talkativeness = num(o.get("talk"), talkativeness);
            riskTolerance = num(o.get("risk"), riskTolerance);
            helpfulness = num(o.get("help"), helpfulness);
            competitiveness = num(o.get("comp"), competitiveness);
            laziness = num(o.get("lazy"), laziness);
            greed = num(o.get("greed"), greed);
        } catch (Exception ignored) {
        }
    }

    private float num(Object v, float def) {
        if (v instanceof Number) {
            return ((Number) v).floatValue();
        }
        return def;
    }
}
