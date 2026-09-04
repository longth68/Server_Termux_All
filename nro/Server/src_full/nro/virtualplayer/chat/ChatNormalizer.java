package nro.virtualplayer.chat;

import java.util.LinkedHashMap;
import java.util.Map;
import java.util.regex.Pattern;

public class ChatNormalizer {

    private static final Map<String, String> TEENCODE_MAP = new LinkedHashMap<>();

    static {
        TEENCODE_MAP.put("k", "không");
        TEENCODE_MAP.put("ko", "không");
        TEENCODE_MAP.put("hơm", "không");
        TEENCODE_MAP.put("hem", "không");
        TEENCODE_MAP.put("khong", "không");
        TEENCODE_MAP.put("dc", "được");
        TEENCODE_MAP.put("dk", "được");
        TEENCODE_MAP.put("duoc", "được");
        TEENCODE_MAP.put("sm", "sức mạnh");
        TEENCODE_MAP.put("tn", "tiềm năng");
        TEENCODE_MAP.put("tv", "thỏi vàng");
        TEENCODE_MAP.put("vtv", "vàng thỏi");
        TEENCODE_MAP.put("ct", "cải trang");
        TEENCODE_MAP.put("caitrang", "cải trang");
        TEENCODE_MAP.put("dt", "đệ tử");
        TEENCODE_MAP.put("de", "đệ");
        TEENCODE_MAP.put("de tu", "đệ tử");
        TEENCODE_MAP.put("kh", "kích hoạt");
        TEENCODE_MAP.put("set kh", "kích hoạt");
        TEENCODE_MAP.put("ib", "nhắn tin");
        TEENCODE_MAP.put("rep", "nhắn tin");
        TEENCODE_MAP.put("pm", "nhắn tin");
        TEENCODE_MAP.put("hp", "máu");
        TEENCODE_MAP.put("mp", "ki");
        TEENCODE_MAP.put("ki", "khí");
        TEENCODE_MAP.put("exp", "kinh nghiệm");
        TEENCODE_MAP.put("lv", "level");
        TEENCODE_MAP.put("pk", "đánh nhau");
        TEENCODE_MAP.put("farming", "cày");
        TEENCODE_MAP.put("train", "cày");
        TEENCODE_MAP.put("farming", "cày");
        TEENCODE_MAP.put("bossing", "đánh boss");
        TEENCODE_MAP.put("trading", "buôn bán");
        TEENCODE_MAP.put("trades", "buôn bán");
        TEENCODE_MAP.put("items", "đồ");
        TEENCODE_MAP.put("item", "đồ");
        TEENCODE_MAP.put("gear", "đồ");
        TEENCODE_MAP.put("price", "giá");
        TEENCODE_MAP.put("cheap", "rẻ");
        TEENCODE_MAP.put("free", "miễn phí");
        TEENCODE_MAP.put("discount", "giảm giá");
        TEENCODE_MAP.put("sale", "giảm giá");
        TEENCODE_MAP.put("deal", "giao dịch");
        TEENCODE_MAP.put("party", "nhóm");
        TEENCODE_MAP.put("team", "nhóm");
        TEENCODE_MAP.put("clan", "bang hội");
        TEENCODE_MAP.put("bang", "bang hội");
        TEENCODE_MAP.put("guild", "bang hội");
        TEENCODE_MAP.put("boss", "boss");
        TEENCODE_MAP.put("raid", "đột kích");
        TEENCODE_MAP.put("add", "kết bạn");
        TEENCODE_MAP.put("added", "kết bạn");
        TEENCODE_MAP.put("quest", "nhiệm vụ");
        TEENCODE_MAP.put("task", "nhiệm vụ");
        TEENCODE_MAP.put("mission", "nhiệm vụ");
        TEENCODE_MAP.put("map", "bản đồ");
        TEENCODE_MAP.put("shop", "cửa hàng");
        TEENCODE_MAP.put("stats", "chỉ số");
        TEENCODE_MAP.put("skill", "kỹ năng");
        TEENCODE_MAP.put("skills", "kỹ năng");
        TEENCODE_MAP.put("spell", "kỹ năng");
        TEENCODE_MAP.put("guild", "bang hội");
        TEENCODE_MAP.put("pet", "thú cưng");
        TEENCODE_MAP.put("reset", "làm lại");
        TEENCODE_MAP.put("upgrade", "nâng cấp");
        TEENCODE_MAP.put("req", "yêu cầu");
        TEENCODE_MAP.put("lvl", "level");
        TEENCODE_MAP.put("pvp", "đánh nhau");
        TEENCODE_MAP.put("solo", "đơn");
        TEENCODE_MAP.put("cheat", "gian lận");
        TEENCODE_MAP.put("bug", "lỗi");
        TEENCODE_MAP.put("lag", "giật");
        TEENCODE_MAP.put("dmg", "sát thương");
        TEENCODE_MAP.put("dame", "sát thương");
        TEENCODE_MAP.put("damage", "sát thương");
        TEENCODE_MAP.put("buff", "tăng");
        TEENCODE_MAP.put("nerf", "giảm");
        TEENCODE_MAP.put("top", "bảng xếp hạng");
        TEENCODE_MAP.put("rank", "hạng");
        TEENCODE_MAP.put("rb", "ruby");
        TEENCODE_MAP.put("ruby", "ngọc");
        TEENCODE_MAP.put("ngoc", "ngọc");
        TEENCODE_MAP.put("gold", "vàng");
        TEENCODE_MAP.put("vang", "vàng");
        TEENCODE_MAP.put("vnd", "vnd");
        TEENCODE_MAP.put("afk", "vắng mặt");
    }

    private static final Pattern MULTI_SPACE = Pattern.compile("\\s+");
    private static final Pattern NO_DIACRITICS = Pattern.compile("(?i)[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]");

    public static String normalize(String input) {
        if (input == null || input.isEmpty()) return input;
        String s = input.toLowerCase().trim();
        s = s.replaceAll("\\s+", " ");
        String[] words = s.split(" ");
        StringBuilder sb = new StringBuilder();
        for (String w : words) {
            String mapped = TEENCODE_MAP.get(w);
            if (mapped != null) {
                sb.append(mapped).append(" ");
            } else {
                sb.append(w).append(" ");
            }
        }
        s = sb.toString().trim();
        s = MULTI_SPACE.matcher(s).replaceAll(" ");
        s = s.replaceAll("(?i)lên([a-zà-ỹ])", "lên $1");
        s = s.replaceAll("(?i)đang([a-zà-ỹ])", "đang $1");
        s = s.replaceAll("(?i)rồi([a-zà-ỹ])", "rồi $1");
        s = s.replaceAll("(?i)mà([a-zà-ỹ])", "mà $1");
        s = s.replaceAll("(?i)thì([a-zà-ỹ])", "thì $1");
        s = s.replaceAll("(?i)là([a-zà-ỹ])", "là $1");
        s = s.replaceAll("(?i)với([a-zà-ỹ])", "với $1");
        s = s.replaceAll("(?i)của([a-zà-ỹ])", "của $1");
        s = s.replaceAll("(?i)mới([a-zà-ỹ])", "mới $1");
        return s;
    }

    public static boolean hasDiacritics(String s) {
        return NO_DIACRITICS.matcher(s).find();
    }
}