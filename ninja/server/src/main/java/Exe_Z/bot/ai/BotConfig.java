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
    /** Population TOI DA toan server (tran). */
    public static int POPULATION = 20;
    /** Spawn mode: GRADUAL (sinh dan theo player online) | PREEXISTING (co san trong map) | MIXED. */
    public static String SPAWN_MODE = "MIXED";
    /** So bot co san (pre-existing) khi khong co ai online. */
    public static int POP_BASE = 6;
    /** So bot THEM moi 1 nguoi choi online (population.perPlayer). */
    public static int POP_PER_PLAYER = 5;
    /** Delay sinh tung bot (ms) — spawn scheduler GRADUAL, khong loat cung luc. */
    public static int SPAWN_MIN_DELAY = 3000;
    public static int SPAWN_MAX_DELAY = 15000;
    /** Bot LUON thap hon player: gap = maxRealLevel - random(minGap..maxGap). */
    public static int PROG_MIN_GAP = 1;
    public static int PROG_MAX_GAP = 3;
    /** Power ratio so voi moc chuan (BOT < PLAYER). */
    public static float POWER_MIN_RATIO = 0.70f;
    public static float POWER_MAX_RATIO = 0.90f;
    /** Khong bao gio follow player (luat cao nhat). */
    public static boolean FOLLOW_PLAYER = false;
    /** Trade AI uu tien tang do hiem cho player. */
    public static boolean TRADE_GIFT_TO_PLAYER = true;
    /** Hệ số EXP bot nhận (0-1, thấp hơn người thật) — theo mẫu Anwin expRate. */
    public static float EXP_RATE = 0.6f;
    public static float GOLD_RATE = 1.0f;
    public static float CHAT_RATE = 1.0f;
    public static float MAP_CHANGE_RATE = 1.0f;
    public static float GIFT_RATE = 1.0f;
    public static float AFK_RATE = 1.0f;
    public static int PLAYER_PROTECTION_PX = 80;
    public static int BOTS_PER_MAP = 3;
    /**
     * Số bot luôn giữ quanh MỖI người chơi thật (mẫu Anwin presencePerPlayer).
     * 0 = bot sống độc lập, không bám theo người chơi.
     */
    public static int PRESENCE_PER_PLAYER = 5;
    /** Thời gian (giây) bot ở quanh người chơi trước khi nhường lượt. */
    public static int PRESENCE_VISIT_SECONDS = 300;

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
            SPAWN_MODE = normMode(p.getProperty("spawn.mode", SPAWN_MODE));
            POP_BASE = clampInt(p.getProperty("population.base"), POP_BASE, 0, 50);
            POP_PER_PLAYER = clampInt(p.getProperty("population.per_player"), POP_PER_PLAYER, 0, 50);
            SPAWN_MIN_DELAY = clampInt(p.getProperty("spawn.min_delay"), SPAWN_MIN_DELAY, 1000, 60000);
            SPAWN_MAX_DELAY = clampInt(p.getProperty("spawn.max_delay"), SPAWN_MAX_DELAY, 1000, 120000);
            PROG_MIN_GAP = clampInt(p.getProperty("progression.min_gap"), PROG_MIN_GAP, 1, 20);
            PROG_MAX_GAP = clampInt(p.getProperty("progression.max_gap"), PROG_MAX_GAP, 1, 30);
            POWER_MIN_RATIO = clampFloat(p.getProperty("power.min_ratio"), POWER_MIN_RATIO, 0.1f, 1.0f);
            POWER_MAX_RATIO = clampFloat(p.getProperty("power.max_ratio"), POWER_MAX_RATIO, 0.1f, 1.0f);
            FOLLOW_PLAYER = "true".equalsIgnoreCase(p.getProperty("follow_player", "false"));
            TRADE_GIFT_TO_PLAYER = !"false".equalsIgnoreCase(p.getProperty("trade.gift_to_player", "true"));
            if (SPAWN_MAX_DELAY < SPAWN_MIN_DELAY) {
                SPAWN_MAX_DELAY = SPAWN_MIN_DELAY;
            }
            if (PROG_MAX_GAP < PROG_MIN_GAP) {
                PROG_MAX_GAP = PROG_MIN_GAP;
            }
            if (POWER_MAX_RATIO < POWER_MIN_RATIO) {
                POWER_MAX_RATIO = POWER_MIN_RATIO;
            }
            EXP_RATE = clampFloat(p.getProperty("exp_rate"), EXP_RATE, 0, 1);
            GOLD_RATE = clampFloat(p.getProperty("gold_rate"), GOLD_RATE, 0, 10);
            CHAT_RATE = clampFloat(p.getProperty("chat_rate"), CHAT_RATE, 0, 10);
            MAP_CHANGE_RATE = clampFloat(p.getProperty("map_change_rate"), MAP_CHANGE_RATE, 0, 10);
            GIFT_RATE = clampFloat(p.getProperty("gift_rate"), GIFT_RATE, 0, 10);
            AFK_RATE = clampFloat(p.getProperty("afk_rate"), AFK_RATE, 0, 10);
            PLAYER_PROTECTION_PX = clampInt(p.getProperty("player_protection"), PLAYER_PROTECTION_PX, 0, 500);
            BOTS_PER_MAP = clampInt(p.getProperty("bots_per_map"), BOTS_PER_MAP, 1, 8);
            PRESENCE_PER_PLAYER = clampInt(p.getProperty("presence_per_player"), PRESENCE_PER_PLAYER, 0, 50);
            PRESENCE_VISIT_SECONDS = clampInt(p.getProperty("presence_visit_seconds"), PRESENCE_VISIT_SECONDS, 30, 3600);
            loaded = true;
        } catch (Exception ignored) {
        }
    }

    public static synchronized void save() {
        try {
            File f = new File(loaded ? FILE : "ninja/server/" + FILE);
            // Ghi tay định dạng key=value với comment ';' để Web PHP đọc được
            // (Java Properties.store dùng '#' + timestamp, PHP parse_ini_file hay báo warning).
            StringBuilder sb = new StringBuilder();
            sb.append("; NSO Bot AI config - BOT luon bat nhu NRO khi enabled=true\n");
            sb.append("enabled=").append(ENABLED).append('\n');
            sb.append("population=").append(POPULATION).append('\n');
            sb.append("spawn.mode=").append(SPAWN_MODE).append('\n');
            sb.append("population.base=").append(POP_BASE).append('\n');
            sb.append("population.per_player=").append(POP_PER_PLAYER).append('\n');
            sb.append("spawn.min_delay=").append(SPAWN_MIN_DELAY).append('\n');
            sb.append("spawn.max_delay=").append(SPAWN_MAX_DELAY).append('\n');
            sb.append("progression.min_gap=").append(PROG_MIN_GAP).append('\n');
            sb.append("progression.max_gap=").append(PROG_MAX_GAP).append('\n');
            sb.append("power.min_ratio=").append(POWER_MIN_RATIO).append('\n');
            sb.append("power.max_ratio=").append(POWER_MAX_RATIO).append('\n');
            sb.append("follow_player=").append(FOLLOW_PLAYER).append('\n');
            sb.append("trade.gift_to_player=").append(TRADE_GIFT_TO_PLAYER).append('\n');
            sb.append("exp_rate=").append(EXP_RATE).append('\n');
            sb.append("gold_rate=").append(GOLD_RATE).append('\n');
            sb.append("chat_rate=").append(CHAT_RATE).append('\n');
            sb.append("map_change_rate=").append(MAP_CHANGE_RATE).append('\n');
            sb.append("gift_rate=").append(GIFT_RATE).append('\n');
            sb.append("afk_rate=").append(AFK_RATE).append('\n');
            sb.append("player_protection=").append(PLAYER_PROTECTION_PX).append('\n');
            sb.append("bots_per_map=").append(BOTS_PER_MAP).append('\n');
            sb.append("presence_per_player=").append(PRESENCE_PER_PLAYER).append('\n');
            sb.append("presence_visit_seconds=").append(PRESENCE_VISIT_SECONDS).append('\n');
            try (OutputStreamWriter w = new OutputStreamWriter(new FileOutputStream(f), StandardCharsets.UTF_8)) {
                w.write(sb.toString());
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
            case "spawn_mode":
            case "spawn.mode":
                SPAWN_MODE = normMode(val);
                break;
            case "population.base":
                POP_BASE = clampInt(val, POP_BASE, 0, 50);
                break;
            case "population.per_player":
            case "per_player":
                POP_PER_PLAYER = clampInt(val, POP_PER_PLAYER, 0, 50);
                break;
            case "spawn.min_delay":
            case "min_delay":
                SPAWN_MIN_DELAY = clampInt(val, SPAWN_MIN_DELAY, 1000, 60000);
                if (SPAWN_MAX_DELAY < SPAWN_MIN_DELAY) SPAWN_MAX_DELAY = SPAWN_MIN_DELAY;
                break;
            case "spawn.max_delay":
            case "max_delay":
                SPAWN_MAX_DELAY = clampInt(val, SPAWN_MAX_DELAY, 1000, 120000);
                if (SPAWN_MAX_DELAY < SPAWN_MIN_DELAY) SPAWN_MIN_DELAY = SPAWN_MAX_DELAY;
                break;
            case "progression.min_gap":
            case "min_gap":
                PROG_MIN_GAP = clampInt(val, PROG_MIN_GAP, 1, 20);
                if (PROG_MAX_GAP < PROG_MIN_GAP) PROG_MAX_GAP = PROG_MIN_GAP;
                break;
            case "progression.max_gap":
            case "max_gap":
                PROG_MAX_GAP = clampInt(val, PROG_MAX_GAP, 1, 30);
                if (PROG_MAX_GAP < PROG_MIN_GAP) PROG_MIN_GAP = PROG_MAX_GAP;
                break;
            case "power.min_ratio":
                POWER_MIN_RATIO = clampFloat(val, POWER_MIN_RATIO, 0.1f, 1.0f);
                break;
            case "power.max_ratio":
                POWER_MAX_RATIO = clampFloat(val, POWER_MAX_RATIO, 0.1f, 1.0f);
                break;
            case "follow_player":
                FOLLOW_PLAYER = "true".equalsIgnoreCase(val);
                break;
            case "trade.gift_to_player":
            case "gift_to_player":
                TRADE_GIFT_TO_PLAYER = !"false".equalsIgnoreCase(val);
                break;
            case "bots_per_map":
                BOTS_PER_MAP = clampInt(val, BOTS_PER_MAP, 1, 8);
                break;
            case "player_protection":
                PLAYER_PROTECTION_PX = clampInt(val, PLAYER_PROTECTION_PX, 0, 500);
                break;
            case "presence_per_player":
                PRESENCE_PER_PLAYER = clampInt(val, PRESENCE_PER_PLAYER, 0, 50);
                break;
            case "presence_visit_seconds":
                PRESENCE_VISIT_SECONDS = clampInt(val, PRESENCE_VISIT_SECONDS, 30, 3600);
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

    private static String normMode(String s) {
        if (s == null) {
            return "MIXED";
        }
        String v = s.trim().toUpperCase();
        for (String m : new String[]{"GRADUAL", "PREEXISTING", "PRE_EXISTING", "MIXED", "DYNAMIC"}) {
            if (m.equals(v)) {
                return m;
            }
        }
        return "MIXED";
    }
}
