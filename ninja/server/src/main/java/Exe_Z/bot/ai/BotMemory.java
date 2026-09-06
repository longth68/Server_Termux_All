package Exe_Z.bot.ai;

import java.util.ArrayDeque;
import java.util.Deque;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import org.json.simple.JSONObject;

/**
 * Port từ NRO VirtualMemory: quan hệ người chơi + chống lặp chat.
 */
public class BotMemory {

    private final Map<String, Integer> relations = new HashMap<>();
    private final Deque<String> recentChats = new ArrayDeque<>();
    private final Map<String, Long> chatCooldowns = new HashMap<>();
    public long lastGreetBot = 0L;

    public void adjustRelation(String name, int delta) {
        if (name == null) {
            return;
        }
        int v = relation(name) + delta;
        if (v < 0) {
            v = 0;
        }
        if (v > 100) {
            v = 100;
        }
        relations.put(name, v);
    }

    public int relation(String name) {
        Integer v = relations.get(name);
        return v == null ? 0 : v;
    }

    public String relationLabel(String name) {
        int v = relation(name);
        if (v >= 70) {
            return "friend";
        }
        if (v >= 30) {
            return "neutral";
        }
        return "stranger";
    }

    /** Số người chơi đã thành bạn (relation >= 70) — hiển thị kiểu NRO. */
    public int countFriends() {
        int n = 0;
        for (Integer v : relations.values()) {
            if (v != null && v >= 70) {
                n++;
            }
        }
        return n;
    }

    public void rememberChat(String line) {
        if (line == null) {
            return;
        }
        recentChats.addLast(line);
        while (recentChats.size() > 8) {
            recentChats.removeFirst();
        }
        chatCooldowns.put(line, System.currentTimeMillis());
    }

    public boolean saidRecently(String line) {
        if (line == null) {
            return true;
        }
        if (recentChats.contains(line)) {
            return true;
        }
        Long t = chatCooldowns.get(line);
        return t != null && System.currentTimeMillis() - t < 60000L;
    }

    /** Chọn câu chưa nói gần đây để bot không lặp. */
    public String pickChat(List<String> pool) {
        if (pool == null || pool.isEmpty()) {
            return null;
        }
        for (String s : pool) {
            if (!saidRecently(s)) {
                return s;
            }
        }
        return pool.get(0);
    }

    @SuppressWarnings("unchecked")
    public JSONObject toSave() {
        JSONObject o = new JSONObject();
        JSONObject rel = new JSONObject();
        for (Map.Entry<String, Integer> e : relations.entrySet()) {
            rel.put(e.getKey(), e.getValue());
        }
        o.put("relations", rel);
        return o;
    }

    public void restore(JSONObject o) {
        if (o == null) {
            return;
        }
        Object rel = o.get("relations");
        if (rel instanceof JSONObject) {
            JSONObject r = (JSONObject) rel;
            for (Object k : r.keySet()) {
                Object v = r.get(k);
                if (v instanceof Number) {
                    relations.put(String.valueOf(k), ((Number) v).intValue());
                }
            }
        }
    }
}
