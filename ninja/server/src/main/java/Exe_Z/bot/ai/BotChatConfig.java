package Exe_Z.bot.ai;

import Exe_Z.util.Log;
import Exe_Z.util.NinjaUtils;
import java.io.BufferedReader;
import java.io.File;
import java.io.FileInputStream;
import java.io.InputStreamReader;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.List;

/**
 * Câu chat tùy chỉnh cho BOT NSO (theo mẫu Anwin VirtualChatConfig).
 * File: ninja/server/bot_chat.txt (UTF-8, 1 câu/dòng, # hoặc ; = comment).
 * Thêm/xóa trực tiếp từ Web Admin, server tự nạp lại mỗi 60 giây.
 */
public class BotChatConfig {

    private static final BotChatConfig INSTANCE = new BotChatConfig();
    private final List<String> lines = new ArrayList<>();
    private long lastLoad = 0L;

    public static BotChatConfig gI() {
        return INSTANCE;
    }

    private BotChatConfig() {
    }

    public synchronized void load() {
        List<String> found = new ArrayList<>();
        for (String path : new String[]{"bot_chat.txt", "ninja/server/bot_chat.txt"}) {
            try {
                File f = new File(path);
                if (!f.exists()) {
                    continue;
                }
                try (BufferedReader br = new BufferedReader(
                        new InputStreamReader(new FileInputStream(f), StandardCharsets.UTF_8))) {
                    String line;
                    while ((line = br.readLine()) != null) {
                        line = line.trim();
                        if (line.isEmpty() || line.startsWith("#") || line.startsWith(";")) {
                            continue;
                        }
                        if (line.length() > 120) {
                            line = line.substring(0, 120);
                        }
                        if (!found.contains(line)) {
                            found.add(line);
                        }
                    }
                }
                break;
            } catch (Exception ex) {
                Log.error("BotChatConfig load err: " + ex.getMessage(), ex);
            }
        }
        lines.clear();
        lines.addAll(found);
        lastLoad = System.currentTimeMillis();
    }

    /** Nạp lại nếu quá 60 giây (Web Admin sửa file xong tự có hiệu lực). */
    public void reloadIfStale() {
        if (System.currentTimeMillis() - lastLoad > 60000L) {
            load();
        }
    }

    public synchronized String randomLine() {
        if (lines.isEmpty()) {
            return null;
        }
        return lines.get(NinjaUtils.nextInt(0, lines.size() - 1));
    }

    public synchronized List<String> getLines() {
        return new ArrayList<>(lines);
    }

    public synchronized int count() {
        return lines.size();
    }
}
