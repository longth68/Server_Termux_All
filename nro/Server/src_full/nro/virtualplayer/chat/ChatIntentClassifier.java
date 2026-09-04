package nro.virtualplayer.chat;

import java.util.LinkedHashMap;
import java.util.Map;
import java.util.regex.Pattern;

public class ChatIntentClassifier {

    public enum Intent {
        GREETING, BUY, SELL, HAGGLE, TRADE_ACCEPT, TRADE_REJECT,
        PK, BOSS, PARTY, HELP, BEG, CLAN,
        QUEST, LEVEL, INFO, FRIEND_REQUEST, EMOTE,
        UNKNOWN
    }

    private static final Map<Intent, Pattern[]> INTENT_PATTERNS = new LinkedHashMap<>();

    static {
        INTENT_PATTERNS.put(Intent.GREETING, new Pattern[]{
            Pattern.compile("\\b(chào|hello|hi|hey|alo|hế lô|hà|ờ|welcome|chao)\\b"),
            Pattern.compile("^(chào|hello|hi|hey|alo)\\b.*")
        });
        INTENT_PATTERNS.put(Intent.BUY, new Pattern[]{
            Pattern.compile("\\b(bán|mua|giá|gia|bao nhiêu|bnhiu|bnhieu|bán cho|cho xin|giá cả|giá bán)\\b"),
            Pattern.compile("\\b(muốn.*mua|cần.*mua|tìm.*mua|hỏi.*giá)\\b"),
            Pattern.compile("\\b(giá.*vậy|giá.*thế|mắc.*quá|đắt.*quá|rẻ.*không|hời.*không)\\b")
        });
        INTENT_PATTERNS.put(Intent.SELL, new Pattern[]{
            Pattern.compile("\\b(có.*bán|bán.*đây|bán.*nè|bán.*này|rao.*bán)\\b"),
            Pattern.compile("\\b(bán.*giá|bán.*rẻ|bán.*hời|thanh.*lý|xả.*kho)\\b"),
            Pattern.compile("\\b(ai.*mua|cần.*mua.*không|ai.*cần)\\b")
        });
        INTENT_PATTERNS.put(Intent.HAGGLE, new Pattern[]{
            Pattern.compile("\\b(bớt|giảm|rẻ.*hơn|giá.*cao.*quá|đắt|mắc|hạ.*giá)\\b"),
            Pattern.compile("\\b(có.*bớt.*không|bớt.*được.*không|giảm.*giá)\\b")
        });
        INTENT_PATTERNS.put(Intent.TRADE_ACCEPT, new Pattern[]{
            Pattern.compile("\\b(ok|đồng ý|chốt|deal|take|buy|mua|giao.*dịch|trao.*đổi|đồng.*ý)\\b"),
            Pattern.compile("\\b(được|lấy|chốt.*giá|chốt.*đơn|done|ok.*deal)\\b")
        });
        INTENT_PATTERNS.put(Intent.TRADE_REJECT, new Pattern[]{
            Pattern.compile("\\b(không.*mua|không.*cần|không.*bán|thôi.*không|đắt.*quá)\\b"),
            Pattern.compile("\\b(không.*đủ.*tiền|hết.*tiền|không.*có.*nhu.*cầu)\\b")
        });
        INTENT_PATTERNS.put(Intent.PK, new Pattern[]{
            Pattern.compile("\\b(pk|đánh.*nhau|đấu|tỷ.*thí|thách.*đấu|solo|1v1)\\b"),
            Pattern.compile("\\b(ra.*map.*\\d+|map.*đấu.*trường|chiến.*đấu)\\b")
        });
        INTENT_PATTERNS.put(Intent.BOSS, new Pattern[]{
            Pattern.compile("\\b(boss|đi.*boss|đánh.*boss|săn.*boss|boss.*đâu|boss.*nào)\\b"),
            Pattern.compile("\\b(spawn|boss.*ra|boss.*xuất.*hiện|phó.*bản|instance|dungeon)\\b")
        });
        INTENT_PATTERNS.put(Intent.PARTY, new Pattern[]{
            Pattern.compile("\\b(team|nhóm|party|đi.*cùng|chơi.*chung|cùng.*chơi)\\b"),
            Pattern.compile("\\b(vào.*nhóm|cho.*vào.*nhóm|mời.*nhóm|nhóm.*không)\\b")
        });
        INTENT_PATTERNS.put(Intent.HELP, new Pattern[]{
            Pattern.compile("\\b(giúp|help|cứu|hỗ.*trợ|chỉ.*giúp|hướng.*dẫn)\\b"),
            Pattern.compile("\\b(làm.*sao|làm.*thế.*nào|cách.*nào|chỉ.*với|dạy.*với)\\b")
        });
        INTENT_PATTERNS.put(Intent.BEG, new Pattern[]{
            Pattern.compile("\\b(xin|cho|give|free|share|chia.*sẻ|cho.*xin)\\b"),
            Pattern.compile("\\b(xin.*ít|cho.*xin.*ít|cho.*đồ|share.*đồ|donate)\\b")
        });
        INTENT_PATTERNS.put(Intent.CLAN, new Pattern[]{
            Pattern.compile("\\b(clan|bang|hội|guild|bang.*hội)\\b"),
            Pattern.compile("\\b(vào.*clan|nhận.*clan|có.*clan.*không|clan.*nào)\\b")
        });
        INTENT_PATTERNS.put(Intent.QUEST, new Pattern[]{
            Pattern.compile("\\b(nhiệm.*vụ|task|quest|nhiệm.*vụ.*chính)\\b"),
            Pattern.compile("\\b(làm.*task|làm.*quest|hoàn.*thành.*nhiệm.*vụ)\\b")
        });
        INTENT_PATTERNS.put(Intent.LEVEL, new Pattern[]{
            Pattern.compile("\\b(level|lv|power|sm|sức.*mạnh|bao.*nhiêu.*level)\\b"),
            Pattern.compile("\\b(lên.*level|cày.*level|train.*level|up.*level)\\b")
        });
        INTENT_PATTERNS.put(Intent.INFO, new Pattern[]{
            Pattern.compile("\\b(có.*gì.*mới|tin.*tức|sự.*kiện|event|info|thông.*tin)\\b"),
            Pattern.compile("\\b(server.*nào|map.*nào|npc.*nào|shop.*đâu)\\b")
        });
        INTENT_PATTERNS.put(Intent.FRIEND_REQUEST, new Pattern[]{
            Pattern.compile("\\b(kết.*bạn|add.*bạn|bạn.*bè|kết.*bạn.*không)\\b"),
            Pattern.compile("\\b(bạn.*nhé|bạn.*nha|add.*nhé|kết.*bạn.*đi)\\b")
        });
        INTENT_PATTERNS.put(Intent.EMOTE, new Pattern[]{
            Pattern.compile("[:;][DPp)(]|<3|:joy:|:cry:|:angry:|:smile:|:sad:|:lol:|:heart:"),
            Pattern.compile("\\b(cười|khóc|giận|vui|buồn|like|haha|huhu|hjhj)\\b")
        });
    }

    public static class ClassificationResult {
        public final Intent intent;
        public final String normalized;
        public final int confidence;

        public ClassificationResult(Intent intent, String normalized, int confidence) {
            this.intent = intent;
            this.normalized = normalized;
            this.confidence = confidence;
        }
    }

    public static ClassificationResult classify(String raw) {
        if (raw == null || raw.isEmpty()) {
            return new ClassificationResult(Intent.UNKNOWN, raw, 0);
        }
        String norm = ChatNormalizer.normalize(raw);
        String lower = norm.toLowerCase();
        int bestScore = 0;
        Intent bestIntent = Intent.UNKNOWN;

        for (Map.Entry<Intent, Pattern[]> entry : INTENT_PATTERNS.entrySet()) {
            int score = 0;
            for (Pattern p : entry.getValue()) {
                java.util.regex.Matcher m = p.matcher(lower);
                if (m.find()) {
                    score += 100 - (m.start() * 2);
                    if (m.start() == 0) score += 20;
                }
            }
            if (score > bestScore) {
                bestScore = score;
                bestIntent = entry.getKey();
            }
        }

        return new ClassificationResult(bestIntent, norm, Math.min(bestScore, 100));
    }
}