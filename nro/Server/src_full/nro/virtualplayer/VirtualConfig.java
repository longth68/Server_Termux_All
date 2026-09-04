package nro.virtualplayer;

import Utils.Logger;
import java.io.File;
import java.nio.file.Files;
import java.util.List;

/**
 * Cấu hình trung tâm của hệ thống Virtual Player.
 * PHASE 7 - Config.
 * Đọc từ virtualplayer_config.txt (key=value, # là comment).
 * Mọi thông số điều chỉnh được theo prompt section 41.
 */
public class VirtualConfig {

    private static VirtualConfig instance;

    public static VirtualConfig gI() {
        if (instance == null) {
            instance = new VirtualConfig();
            instance.load();
        }
        return instance;
    }

    // ===== GIÁ TRỊ CẤU HÌNH =====
    public boolean enabled = true;
    public int population = 30;

    /** Hệ số EXP bot nhận khi phát triển (0-1). Thấp hơn player thật. */
    public float expRate = 0.6f;
    /** Hệ số vàng bot thu được qua farm (0-1). */
    public float goldRate = 0.7f;
    /** Hệ số chat (0 = im lặng, 1 = nói nhiều hơn bình thường). */
    public float chatRate = 0.4f;
    /** Hệ số đổi map (0 = ít đi, 1 = hay đi). */
    public float mapChangeRate = 0.3f;
    /** Bảo vệ player: bot không vượt player mạnh nhất × catchupPercent. */
    public boolean playerProtection = true;
    /** Tần suất bot tặng đồ cho player (0 = không bao giờ, 1 = rất hay). */
    public float giftRate = 0.5f;
    /** Tần suất AFK/logout (0 = hiếm, 1 = thường xuyên). */
    public float afkRate = 0.3f;

    /**
     * Số bot AI luôn giữ hoạt động gần MỖI người chơi thật (hạn mức hiện diện).
     * 0 = tắt hiện diện, bot sống HOÀN TOÀN độc lập (đúng thiết kế gốc).
     * >0 = ngoài đời sống độc lập, luôn có chừng này bot 'ghé thăm' luân phiên quanh player.
     */
    public int presencePerPlayer = 5;
    /** Thời gian (giây) một bot 'ghé thăm' khu người chơi trước khi nhường lượt cho bot khác. */
    public int presenceVisitSeconds = 300;

    private static final String CONFIG_FILE = "virtualplayer_config.txt";

    private VirtualConfig() {}

    public void load() {
        try {
            File f = new File(CONFIG_FILE);
            if (!f.exists()) {
                Logger.system("VPConfig", "Không tìm thấy " + CONFIG_FILE + ", dùng mặc định");
                return;
            }
            List<String> lines = Files.readAllLines(f.toPath());
            for (String line : lines) {
                line = line.trim();
                if (line.isEmpty() || line.startsWith("#")) continue;
                int eq = line.indexOf('=');
                if (eq < 0) continue;
                String key = line.substring(0, eq).trim().toLowerCase();
                String val = line.substring(eq + 1).trim();

                switch (key) {
                    case "enabled":
                        enabled = parseBool(val, enabled);
                        break;
                    case "population":
                        population = clamp(parseInt(val, population), 0, 500);
                        break;
                    case "exp_rate":
                        expRate = clamp(parseF(val, expRate), 0f, 1f);
                        break;
                    case "gold_rate":
                        goldRate = clamp(parseF(val, goldRate), 0f, 1f);
                        break;
                    case "chat_rate":
                        chatRate = clamp(parseF(val, chatRate), 0f, 1f);
                        break;
                    case "map_change_rate":
                        mapChangeRate = clamp(parseF(val, mapChangeRate), 0f, 1f);
                        break;
                    case "player_protection":
                        playerProtection = parseBool(val, playerProtection);
                        break;
                    case "gift_rate":
                        giftRate = clamp(parseF(val, giftRate), 0f, 1f);
                        break;
                    case "afk_rate":
                        afkRate = clamp(parseF(val, afkRate), 0f, 1f);
                        break;
                    case "presence_per_player":
                        presencePerPlayer = clamp(parseInt(val, presencePerPlayer), 0, 50);
                        break;
                    case "presence_visit_seconds":
                        presenceVisitSeconds = clamp(parseInt(val, presenceVisitSeconds), 30, 3600);
                        break;
                    default:
                        break;
                }
            }
        } catch (Exception e) {
            Logger.logException(VirtualConfig.class, e);
        }
    }

    private boolean parseBool(String v, boolean def) {
        return v.equalsIgnoreCase("true") || v.equals("1") ? true
                : v.equalsIgnoreCase("false") || v.equals("0") ? false : def;
    }

    private int parseInt(String v, int def) {
        try { return Integer.parseInt(v); } catch (Exception e) { return def; }
    }

    private float parseF(String v, float def) {
        try { return Float.parseFloat(v); } catch (Exception e) { return def; }
    }

    private int clamp(int v, int min, int max) {
        return Math.max(min, Math.min(max, v));
    }

    private float clamp(float v, float min, float max) {
        return Math.max(min, Math.min(max, v));
    }
}