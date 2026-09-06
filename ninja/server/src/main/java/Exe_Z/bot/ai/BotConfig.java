package Exe_Z.bot.ai;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.OutputStreamWriter;
import java.nio.charset.StandardCharsets;
import java.util.Properties;

/**
 * Port từ NRO VirtualConfig: file bot_config.txt (9 tham số).
 * Tương thích Termux: đọc/ghi file text đơn giản, không đụng DB.
 */
public class BotConfig {

    public static boolean ENABLED = true;
    public static int POPULATION = 20;
    public static float GOLD_RATE = 1.0f;
    public static float CHAT_RATE = 1.0f;
    public static float MAP_CHANGE_RATE = 1.0f;
    public static float GIFT_RATE = 1.0f;
    public static float AFK_RATE = 1.0f;
    public static int PLAYER_PROTECTION_PX = 80;
    public static int BOTS_PER_MAP = 3;

    private static final String FILE = "bot_config.txt";
    private static boolean loaded = false;

    public static synchronized void load() {
        load(new File(FILE));
        load(new File("ninja/server/" + FILE));
    }

    private static synchronized void load(File f) {
        if (!f.exists()) {
            return;
        }
        try (FileInputStream in = new FileInputStream(f)) {
            Properties p = new Properties();
            p.load(in);
            ENABLED = !"false".equalsIgnoreCase(p.getProperty("enabled", "true"));
            POPULATION = clampInt(p.getProperty("population"), POPULATION, 0, 200);
            GOLD_RATE = clampFloat(p.getProperty("gold_rate"), GOLD_RATE, 0, 10);
            CHAT_RATE = clampFloat(p.getProperty("chat_rate"), CHAT_RATE, 0, 10);
            MAP_CHANGE_RATE = clampFloat(p.getProperty("map_change_rate"), MAP_CHANGE_RATE, 0, 10);
            GIFT_RATE = clampFloat(p.getProperty("gift_rate"), GIFT_RATE, 0, 10);
            AFK_RATE = clampFloat(p.getProperty("afk_rate"), AFK_RATE, 0, 10);
            PLAYER_PROTECTION_PX = clampInt(p.getProperty("player_protection"), PLAYER_PROTECTION_PX, 0, 500);
            BOTS_PER_MAP = clampInt(p.getProperty("bots_per_map"), BOTS_PER_MAP, 1, 8);
            loaded = true;
        } catch (Exception ignored) {
        }
    }

    public static synchronized void save() {
        try {
            File f = new File(loaded ? FILE : "ninja/server/" + FILE);
            Properties p = new Properties();
            p.setProperty("enabled", String.valueOf(ENABLED));
            p.setProperty("population", String.valueOf(POPULATION));
            p.setProperty("gold_rate", String.valueOf(GOLD_RATE));
            p.setProperty("chat_rate", String.valueOf(CHAT_RATE));
            p.setProperty("map_change_rate", String.valueOf(MAP_CHANGE_RATE));
            p.setProperty("gift_rate", String.valueOf(GIFT_RATE));
            p.setProperty("afk_rate", String.valueOf(AFK_RATE));
            p.setProperty("player_protection", String.valueOf(PLAYER_PROTECTION_PX));
            p.setProperty("bots_per_map", String.valueOf(BOTS_PER_MAP));
            try (OutputStreamWriter w = new OutputStreamWriter(new FileOutputStream(f), StandardCharsets.UTF_8)) {
                p.store(w, "NSO Bot AI config (port tu NRO VirtualConfig)");
            }
        } catch (Exception ignored) {
        }
    }

    public static void set(String key, String val) {
        load();
        if (key == null || val == null) {
            return;
        }
        switch (key) {
            case "enabled":
                ENABLED = !"false".equalsIgnoreCase(val);
                break;
            case "population":
                POPULATION = clampInt(val, POPULATION, 0, 200);
                break;
            case "bots_per_map":
                BOTS_PER_MAP = clampInt(val, BOTS_PER_MAP, 1, 8);
                break;
            case "player_protection":
                PLAYER_PROTECTION_PX = clampInt(val, PLAYER_PROTECTION_PX, 0, 500);
                break;
            default:
                break;
        }
        save();
    }

    private static int clampInt(String s, int def, int min, int max) {
        try {
            int v = Integer.parseInt(s.trim());
            if (v < min) {
                v = min;
            }
            if (v > max) {
                v = max;
            }
            return v;
        } catch (Exception e) {
            return def;
        }
    }

    private static float clampFloat(String s, float def, float min, float max) {
        try {
            float v = Float.parseFloat(s.trim());
            if (v < min) {
                v = min;
            }
            if (v > max) {
                v = max;
            }
            return v;
        } catch (Exception e) {
            return def;
        }
    }
}
