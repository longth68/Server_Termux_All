package Exe_Z.bot.ai;

import java.util.HashMap;
import java.util.Map;
import org.json.simple.JSONObject;

/**
 * Port từ NRO VirtualNeeds: 7 nhu cầu, grow() theo thời gian,
 * topNeed() để Brain ra quyết định, satisfy() khi hoàn thành.
 * Không đụng DB schoolzz, chỉ lưu JSON file.
 */
public class BotNeeds {

    public static final String EXP = "exp";
    public static final String GOLD = "gold";
    public static final String ITEM = "item";
    public static final String QUEST = "quest";
    public static final String SOCIAL = "social";
    public static final String REST = "rest";
    public static final String EXPLORE = "explore";

    private final Map<String, Double> values = new HashMap<>();
    private long lastGrow = System.currentTimeMillis();

    public BotNeeds() {
        values.put(EXP, 1.0);
        values.put(GOLD, 1.0);
        values.put(ITEM, 1.0);
        values.put(QUEST, 0.5);
        values.put(SOCIAL, 0.5);
        values.put(REST, 0.0);
        values.put(EXPLORE, 0.5);
    }

    /** Nhu cầu tăng dần, gọi mỗi tick (giới hạn 1 lần/giây). */
    public void grow() {
        long now = System.currentTimeMillis();
        if (now - lastGrow < 1000L) {
            return;
        }
        lastGrow = now;
        add(EXP, 0.02);
        add(GOLD, 0.015);
        add(ITEM, 0.012);
        add(EXPLORE, 0.01);
        add(SOCIAL, 0.008);
        add(QUEST, 0.005);
        // REST chỉ tăng khi HP/MP thấp (do BotBrain bơm thêm)
    }

    public double get(String key) {
        Double v = values.get(key);
        return v == null ? 0.0 : v;
    }

    public void satisfy(String key, double amount) {
        add(key, -Math.abs(amount));
    }

    public void addNeed(String key, double amount) {
        add(key, amount);
    }

    private void add(String key, double d) {
        double v = get(key) + d;
        if (v < 0) {
            v = 0;
        }
        if (v > 100) {
            v = 100;
        }
        values.put(key, v);
    }

    public String topNeed() {
        String best = EXP;
        double bv = -1;
        for (Map.Entry<String, Double> e : values.entrySet()) {
            if (e.getValue() > bv) {
                bv = e.getValue();
                best = e.getKey();
            }
        }
        return best;
    }

    @SuppressWarnings("unchecked")
    public JSONObject toSave() {
        JSONObject o = new JSONObject();
        for (Map.Entry<String, Double> e : values.entrySet()) {
            o.put(e.getKey(), e.getValue());
        }
        return o;
    }

    public void restore(JSONObject o) {
        if (o == null) {
            return;
        }
        for (String k : new String[]{EXP, GOLD, ITEM, QUEST, SOCIAL, REST, EXPLORE}) {
            Object v = o.get(k);
            if (v instanceof Number) {
                values.put(k, ((Number) v).doubleValue());
            }
        }
    }
}
