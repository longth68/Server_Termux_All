package nro.virtualplayer.core;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.Random;

/**
 * Bộ nhớ & mối quan hệ của Virtual Player.
 * PHASE 2 - Virtual Player Core.
 * Ghi nhớ: người chơi thật, quan hệ (FRIEND/NEUTRAL/RIVAL/UNTRUSTED),
 * lịch sử chat gần đây, đối thủ, bạn party.
 */
public class VirtualMemory {

    public enum Relationship { FRIEND, NEUTRAL, RIVAL, UNTRUSTED }

    private final Map<String, Float> relationScore = new HashMap<>(); // playerName -> -100..100
    private final List<String> recentChat = new ArrayList<>();
    private final List<String> partyHistory = new ArrayList<>();
    private final Map<String, String> knownPlayers = new HashMap<>(); // name -> relationship string
    private final List<String> rivalPlayers = new ArrayList<>();
    private final Random random = new Random();

    public static final int MAX_CHAT_HISTORY = 40;
    public static final int MAX_PARTY_HISTORY = 20;

    // ===== RELATIONSHIP =====
    public synchronized void adjustRelation(String playerName, float delta) {
        if (playerName == null || playerName.isEmpty()) return;
        float cur = relationScore.getOrDefault(playerName, 0f);
        cur = Math.max(-100, Math.min(100, cur + delta));
        relationScore.put(playerName, cur);
        knownPlayers.put(playerName, relationshipOf(cur).name());
        if (relationshipOf(cur) == Relationship.RIVAL && !rivalPlayers.contains(playerName)) {
            rivalPlayers.add(playerName);
        }
    }

    public float getRelationScore(String playerName) {
        return relationScore.getOrDefault(playerName, 0f);
    }

    public Relationship getRelationship(String playerName) {
        return relationshipOf(getRelationScore(playerName));
    }

    private Relationship relationshipOf(float score) {
        if (score >= 30) return Relationship.FRIEND;
        if (score <= -30) return Relationship.UNTRUSTED;
        if (score <= -10) return Relationship.RIVAL;
        return Relationship.NEUTRAL;
    }

    public boolean isFriend(String playerName) {
        return getRelationship(playerName) == Relationship.FRIEND;
    }

    public boolean isRival(String playerName) {
        return getRelationship(playerName) == Relationship.RIVAL || rivalPlayers.contains(playerName);
    }

    public List<String> getRivals() { return rivalPlayers; }

    // ===== CHAT HISTORY =====
    public synchronized void rememberChat(String msg) {
        recentChat.add(msg);
        if (recentChat.size() > MAX_CHAT_HISTORY) {
            recentChat.remove(0);
        }
    }

    public boolean recentlySaid(String msg) {
        return recentChat.contains(msg);
    }

    public boolean hasSaidRecently(String keyword) {
        for (String m : recentChat) {
            if (m != null && m.contains(keyword)) return true;
        }
        return false;
    }

    // ===== PARTY HISTORY =====
    public synchronized void rememberParty(String playerName) {
        partyHistory.add(playerName);
        if (partyHistory.size() > MAX_PARTY_HISTORY) {
            partyHistory.remove(0);
        }
    }

    public boolean hasPartiedWith(String playerName) {
        return partyHistory.contains(playerName);
    }

    // ===== CACHE AVOIDANCE (tránh lặp) =====
    public String pickChat(String[] pool) {
        if (pool == null || pool.length == 0) return null;
        // Thử vài lần tìm câu chưa nói gần đây
        for (int attempt = 0; attempt < 6; attempt++) {
            String m = pool[random.nextInt(pool.length)];
            if (!recentlySaid(m)) {
                rememberChat(m);
                return m;
            }
        }
        String fallback = pool[random.nextInt(pool.length)];
        rememberChat(fallback);
        return fallback;
    }

    public Map<String, Float> getRelationScores() {
        return relationScore;
    }
}
