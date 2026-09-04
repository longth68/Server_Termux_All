package nro.bot.ai;

import java.util.HashMap;
import java.util.Map;

public class BotMemory {
    
    // Lưu trữ quan hệ (Relationship) với các Player khác (ID -> Điểm số)
    // Điểm > 0: Tốt (Party, giúp đỡ)
    // Điểm < 0: Xấu (Tranh quái, PK)
    public Map<Long, Integer> playerRelationships = new HashMap<>();
    
    // Lưu trữ danh sách mục tiêu bị KS
    public Map<Integer, Long> monsterReserved = new HashMap<>(); // Mob ID -> Time Reserved
    
    public PlayerLeaderInfo leaderInfo = null;
    
    public class PlayerLeaderInfo {
        public long playerId;
        public long lastTimeSeen;
    }

    public void addRelationship(long playerId, int score) {
        int current = playerRelationships.getOrDefault(playerId, 0);
        playerRelationships.put(playerId, current + score);
    }
    
    public int getRelationship(long playerId) {
        return playerRelationships.getOrDefault(playerId, 0);
    }
}
