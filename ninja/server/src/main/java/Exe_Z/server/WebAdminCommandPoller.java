/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package Exe_Z.server;

import Exe_Z.bot.AutoFarmBot;
import Exe_Z.db.jdbc.DbManager;
import Exe_Z.model.Char;
import Exe_Z.model.User;
import Exe_Z.util.Log;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.List;
import org.json.simple.JSONObject;
import org.json.simple.parser.JSONParser;
import org.json.simple.parser.ParseException;

/**
 * Poller lệnh quản trị từ Web Admin (giống HTTH): đọc bảng web_admin_commands,
 * thực thi các lệnh SEND_NOTICE, KICK, BAN, SPAWN_BOT, KILL_BOT, SPAWN_BOSS,
 * SERVER_CONTROL, UPDATE_SERVER_CONFIG rồi đánh dấu status = 1.
 *
 * @author longth68
 */
public class WebAdminCommandPoller extends Thread {

    private boolean isRunning = true;
    private static final long POLL_INTERVAL = 3000L;
    private static final int MAX_COMMANDS_PER_CYCLE = 20;
    private final JSONParser parser = new JSONParser();

    public WebAdminCommandPoller() {
        this.setName("WebAdminCommandPoller");
        this.setDaemon(true);
    }

    public void stopPoller() {
        this.isRunning = false;
    }

    @Override
    public void run() {
        while (isRunning) {
            try {
                Thread.sleep(POLL_INTERVAL);
                processCommands();
            } catch (InterruptedException e) {
                break;
            } catch (Exception e) {
                Log.error("WebAdminCommandPoller err: " + e.getMessage(), e);
            }
        }
    }

    private void processCommands() {
        List<Integer> processed = new ArrayList<>();
        Connection conn = null;
        PreparedStatement st = null;
        ResultSet rs = null;
        try {
            conn = DbManager.getInstance().getConnection(DbManager.GAME);
            st = conn.prepareStatement(
                    "SELECT `id`, `command`, `target_user`, `data` FROM `web_admin_commands` WHERE `status` = 0 ORDER BY `id` ASC LIMIT "
                            + MAX_COMMANDS_PER_CYCLE + ";");
            rs = st.executeQuery();
            while (rs.next()) {
                int id = rs.getInt("id");
                String command = rs.getString("command");
                String targetUser = rs.getString("target_user");
                String data = rs.getString("data");
                try {
                    executeCommand(command, targetUser, data);
                } catch (Exception ex) {
                    Log.error("WebAdminCommand ID " + id + " err: " + ex.getMessage(), ex);
                }
                processed.add(id);
            }
        } catch (SQLException e) {
            Log.error("WebAdminCommandPoller query err: " + e.getMessage(), e);
        } finally {
            close(rs);
            close(st);
            close(conn);
        }
        if (!processed.isEmpty()) {
            StringBuilder ids = new StringBuilder();
            for (int i = 0; i < processed.size(); i++) {
                if (i > 0) {
                    ids.append(",");
                }
                ids.append(processed.get(i));
            }
            Connection upConn = null;
            Statement upSt = null;
            try {
                upConn = DbManager.getInstance().getConnection(DbManager.GAME);
                upSt = upConn.createStatement();
                upSt.executeUpdate("UPDATE `web_admin_commands` SET `status` = 1 WHERE `id` IN (" + ids + ");");
            } catch (SQLException e) {
                Log.error("WebAdminCommandPoller update err: " + e.getMessage(), e);
            } finally {
                close(upSt);
                close(upConn);
            }
        }
        updateServerStatus();
    }

    private void updateServerStatus() {
        Connection conn = null;
        PreparedStatement st = null;
        try {
            int online = ServerManager.getNumberOnline();
            int bots = AutoFarmBot.count();
            long mem = (Runtime.getRuntime().totalMemory() - Runtime.getRuntime().freeMemory()) / (1024 * 1024);
            conn = DbManager.getInstance().getConnection(DbManager.GAME);
            st = conn.prepareStatement(
                    "INSERT INTO `server_status` (`id`, `online`, `bots`, `memory_mb`, `updated_at`) VALUES (1, ?, ?, ?, NOW()) "
                            + "ON DUPLICATE KEY UPDATE `online` = ?, `bots` = ?, `memory_mb` = ?, `updated_at` = NOW();");
            st.setInt(1, online);
            st.setInt(2, bots);
            st.setLong(3, mem);
            st.setInt(4, online);
            st.setInt(5, bots);
            st.setLong(6, mem);
            st.executeUpdate();
        } catch (SQLException e) {
            Log.error("WebAdminCommandPoller status err: " + e.getMessage(), e);
        } finally {
            close(st);
            close(conn);
        }
        updateBotStatus();
    }

    /** Ghi snapshot chi tiết từng bot vào bảng bot_status (tự tạo bảng nếu chưa có). */
    private void updateBotStatus() {
        Connection conn = null;
        Statement create = null;
        PreparedStatement del = null;
        PreparedStatement ins = null;
        try {
            conn = DbManager.getInstance().getConnection(DbManager.GAME);
            create = conn.createStatement();
            create.executeUpdate("CREATE TABLE IF NOT EXISTS `bot_status` ("
                    + "`id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(50) NOT NULL, "
                    + "`level` int(11) NOT NULL DEFAULT 1, `map_id` int(11) NOT NULL DEFAULT 0, "
                    + "`zone_id` int(11) NOT NULL DEFAULT 0, `x` int(11) NOT NULL DEFAULT 0, "
                    + "`y` int(11) NOT NULL DEFAULT 0, `hp` bigint(20) NOT NULL DEFAULT 0, "
                    + "`max_hp` bigint(20) NOT NULL DEFAULT 0, `state` varchar(30) DEFAULT '', "
                    + "`personality` varchar(255) DEFAULT '', `top_need` varchar(30) DEFAULT '', "
                    + "`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP, "
                    + "PRIMARY KEY (`id`), UNIQUE KEY `name` (`name`)) "
                    + "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            java.util.List<JSONObject> bots = AutoFarmBot.snapshotInfo(200);
            del = conn.prepareStatement("DELETE FROM `bot_status`;");
            del.executeUpdate();
            if (bots.isEmpty()) {
                return;
            }
            ins = conn.prepareStatement("INSERT INTO `bot_status` "
                    + "(`name`,`level`,`map_id`,`zone_id`,`x`,`y`,`hp`,`max_hp`,`state`,`personality`,`top_need`,`updated_at`) "
                    + "VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW());");
            for (JSONObject b : bots) {
                ins.setString(1, str(b.get("name")));
                ins.setInt(2, num(b.get("level")));
                ins.setInt(3, num(b.get("map_id")));
                ins.setInt(4, num(b.get("zone_id")));
                ins.setInt(5, num(b.get("x")));
                ins.setInt(6, num(b.get("y")));
                ins.setLong(7, lng(b.get("hp")));
                ins.setLong(8, lng(b.get("max_hp")));
                ins.setString(9, str(b.get("state")));
                ins.setString(10, str(b.get("personality")));
                ins.setString(11, str(b.get("top_need")));
                ins.addBatch();
            }
            ins.executeBatch();
        } catch (Exception e) {
            Log.error("WebAdminCommandPoller bot_status err: " + e.getMessage(), e);
        } finally {
            close(ins);
            close(del);
            close(create);
            close(conn);
        }
    }

    private String str(Object v) {
        return v == null ? "" : String.valueOf(v);
    }

    private int num(Object v) {
        if (v instanceof Number) {
            return ((Number) v).intValue();
        }
        try {
            return Integer.parseInt(String.valueOf(v));
        } catch (Exception e) {
            return 0;
        }
    }

    private long lng(Object v) {
        if (v instanceof Number) {
            return ((Number) v).longValue();
        }
        try {
            return Long.parseLong(String.valueOf(v));
        } catch (Exception e) {
            return 0L;
        }
    }

    private void killOneBot(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String name = (String) obj.get("name");
        if (name == null || name.isEmpty()) {
            return;
        }
        boolean ok = AutoFarmBot.removeByName(name);
        Log.info("[WebAdmin] KILL_ONE_BOT name=" + name + " ok=" + ok);
    }

    private void executeCommand(String command, String targetUser, String data) throws Exception {
        if (command == null) {
            return;
        }
        switch (command) {
            case "SEND_NOTICE":
                sendNotice(data);
                break;
            case "KICK":
                kick(targetUser);
                break;
            case "BAN":
                ban(targetUser);
                break;
            case "SPAWN_BOT":
                spawnBot(data);
                break;
            case "KILL_BOT":
                AutoFarmBot.removeAll();
                Log.info("[WebAdmin] KILL_BOT: đã xóa toàn bộ bot.");
                break;
            case "KILL_ONE_BOT":
                killOneBot(data);
                break;
            case "BOT_CONFIG":
                botConfig(data);
                break;
            case "RELOAD_DROP":
                reloadDrop(data);
                break;
            case "SKIP_TASK":
                skipTask(data);
                break;
            case "TEST_BOT":
                testBot(data);
                break;
            case "SPAWN_BOSS":
                spawnBoss(data);
                break;
            case "SERVER_CONTROL":
                serverControl(data);
                break;
            case "UPDATE_SERVER_CONFIG":
                Config.getInstance().reload();
                Config.getInstance().reloadnjtl();
                GlobalService.getInstance().chat("Hệ thống", "Cấu hình máy chủ đã được cập nhật từ Web Admin.");
                Log.info("[WebAdmin] UPDATE_SERVER_CONFIG done.");
                break;
            default:
                Log.warn("[WebAdmin] Lệnh không hỗ trợ: " + command);
                break;
        }
    }

    private void sendNotice(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String title = (String) obj.get("title");
        String content = (String) obj.get("content");
        String message = (String) obj.get("message");
        if (message == null || message.isEmpty()) {
            if (title == null || title.isEmpty()) {
                title = "Hệ thống";
            }
            if (content == null || content.isEmpty()) {
                return;
            }
            message = "[" + title + "] " + content;
        }
        GlobalService.getInstance().chat("Hệ thống", message);
        GlobalService.getInstance().showAlert("Hệ thống", message);
        Log.info("[WebAdmin] SEND_NOTICE: " + message);
    }

    private void kick(String charName) {
        if (charName == null || charName.isEmpty()) {
            return;
        }
        Char _char = ServerManager.findCharByName(charName);
        if (_char != null && _char.user != null && _char.user.session != null) {
            try {
                _char.user.session.disconnect();
            } catch (Exception e) {
                Log.error("WebAdmin KICK err: " + e.getMessage(), e);
            }
        }
        Log.info("[WebAdmin] KICK: " + charName);
    }

    private void ban(String charName) {
        if (charName == null || charName.isEmpty()) {
            return;
        }
        Char _char = ServerManager.findCharByName(charName);
        User user = _char != null ? _char.user : ServerManager.findUserByUsername(charName);
        if (user != null) {
            try {
                user.lock();
            } catch (Exception e) {
                Log.error("WebAdmin BAN err: " + e.getMessage(), e);
            }
            Log.info("[WebAdmin] BAN: " + charName);
            return;
        }
        Connection conn = null;
        PreparedStatement st = null;
        try {
            conn = DbManager.getInstance().getConnection(DbManager.GAME);
            st = conn.prepareStatement("UPDATE `users` SET `status` = 0 WHERE `username` = ? LIMIT 1;");
            st.setString(1, charName);
            st.executeUpdate();
            Log.info("[WebAdmin] BAN(offline): " + charName);
        } catch (SQLException e) {
            Log.error("WebAdmin BAN offline err: " + e.getMessage(), e);
        } finally {
            close(st);
            close(conn);
        }
    }

    private void spawnBot(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        int map = obj.get("map") != null ? ((Number) obj.get("map")).intValue() : 0;
        int count = obj.get("count") != null ? ((Number) obj.get("count")).intValue() : 1;
        int level = obj.get("level") != null ? ((Number) obj.get("level")).intValue() : 10;
        int hp = obj.get("hp") != null ? ((Number) obj.get("hp")).intValue() : 20000;
        int damage = obj.get("damage") != null ? ((Number) obj.get("damage")).intValue() : 1500;
        int speed = obj.get("speed") != null ? ((Number) obj.get("speed")).intValue() : 0;
        int spawned = AutoFarmBot.spawnByMap(map, Math.max(1, count), level, hp, damage, speed);
        if (spawned > 0) {
            GlobalService.getInstance().chat("Hệ thống", "Web Admin đã triệu hồi " + spawned + " bot tại map " + map + ".");
        }
        Log.info("[WebAdmin] SPAWN_BOT map=" + map + " count=" + count + " spawned=" + spawned);
    }

    // Cập nhật cấu hình BOT AI (port NRO VirtualConfig): enabled/population/rates/protection
    private void botConfig(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        Exe_Z.bot.ai.BotConfig.load();
        if (obj.get("enabled") != null) {
            Object v = obj.get("enabled");
            Exe_Z.bot.ai.BotConfig.ENABLED = v instanceof Boolean ? (Boolean) v
                    : !"false".equalsIgnoreCase(String.valueOf(v));
        }
        if (obj.get("population") != null) {
            Exe_Z.bot.ai.BotConfig.POPULATION = clamp(((Number) obj.get("population")).intValue(), 0, 200);
        }
        if (obj.get("bots_per_map") != null) {
            Exe_Z.bot.ai.BotConfig.BOTS_PER_MAP = clamp(((Number) obj.get("bots_per_map")).intValue(), 1, 8);
        }
        if (obj.get("player_protection") != null) {
            Exe_Z.bot.ai.BotConfig.PLAYER_PROTECTION_PX = clamp(((Number) obj.get("player_protection")).intValue(), 0, 500);
        }
        if (obj.get("chat_rate") != null) {
            Exe_Z.bot.ai.BotConfig.CHAT_RATE = ((Number) obj.get("chat_rate")).floatValue();
        }
        if (obj.get("map_change_rate") != null) {
            Exe_Z.bot.ai.BotConfig.MAP_CHANGE_RATE = ((Number) obj.get("map_change_rate")).floatValue();
        }
        if (obj.get("gift_rate") != null) {
            Exe_Z.bot.ai.BotConfig.GIFT_RATE = ((Number) obj.get("gift_rate")).floatValue();
        }
        if (obj.get("afk_rate") != null) {
            Exe_Z.bot.ai.BotConfig.AFK_RATE = ((Number) obj.get("afk_rate")).floatValue();
        }
        if (obj.get("gold_rate") != null) {
            Exe_Z.bot.ai.BotConfig.GOLD_RATE = ((Number) obj.get("gold_rate")).floatValue();
        }
        Exe_Z.bot.ai.BotConfig.save();
        GlobalService.getInstance().chat("Hệ thống",
                "Web Admin đã cập nhật cấu hình BOT AI (pop=" + Exe_Z.bot.ai.BotConfig.POPULATION
                        + ", per_map=" + Exe_Z.bot.ai.BotConfig.BOTS_PER_MAP + ").");
        Log.info("[WebAdmin] BOT_CONFIG done: " + obj.toJSONString());
    }

    private int clamp(int v, int min, int max) {
        if (v < min) {
            return min;
        }
        return Math.min(v, max);
    }

    // Bỏ qua nhiệm vụ hiện tại của người chơi, nhảy sang nhiệm vụ tiếp theo
    private void skipTask(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String name = (String) obj.get("name");
        Integer steps = obj.get("steps") == null ? 1 : ((Number) obj.get("steps")).intValue();
        if (name == null || name.isEmpty()) {
            Log.warn("[WebAdmin] SKIP_TASK thiếu tên nhân vật.");
            return;
        }
        Char c = Exe_Z.server.ServerManager.findCharByName(name);
        if (c == null) {
            Log.warn("[WebAdmin] SKIP_TASK: không tìm thấy nhân vật online: " + name);
            return;
        }
        steps = Math.max(1, Math.min(steps, 10));
        for (int i = 0; i < steps; i++) {
            c.skipTask();
        }
        Log.info("[WebAdmin] SKIP_TASK: " + name + " bỏ qua " + steps + " nhiệm vụ.");
    }

    // Nạp lại tỉ lệ rơi đồ từ các file JSON trong item_roi (hot-reload)
    private void reloadDrop(String data) {
        String[] dirs = {
            "item_roi/event_Halloween", "item_roi/event_LunarNewYear", "item_roi/event_Noel",
            "item_roi/event_SumMer", "item_roi/event_NhaGiaoVN", "item_roi/event_TrungThu",
            "item_roi/loai_khac", "item_roi/map_LDGT", "item_roi/map_VDMQ",
            "item_roi/map_langco", "item_roi/map_langtruyenthuyet", "item_roi/map_thuong",
            "item_roi/LatHinh"
        };
        int loaded = 0;
        for (String dir : dirs) {
            try {
                Exe_Z.model.RandomItem.abc(dir);
                loaded++;
            } catch (Exception e) {
                Log.error("reloadDrop err " + dir + ": " + e.getMessage(), e);
            }
        }
        GlobalService.getInstance().chat("Hệ thống", "Web Admin đã tải lại tỉ lệ rơi đồ (item_roi).");
        Log.info("[WebAdmin] RELOAD_DROP done (" + loaded + " thư mục).");
    }

    private void testBot(String data) {        JSONObject obj;
        try {
            obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        } catch (ParseException e) {
            obj = new JSONObject();
        }
        int map = obj.get("map") != null ? ((Number) obj.get("map")).intValue() : 1;
        String result = AutoFarmBot.testFeatures(map);
        // Ghi kết quả vào log server để dễ kiểm chứng
        for (String line : result.split("\n")) {
            Log.info("[TEST_BOT] " + line);
        }
        // Thông báo qua global chat
        GlobalService.getInstance().chat("Hệ thống", "Web Admin đã chạy kiểm chứng tính năng bot tại map " + map + ". Xem log server.");
    }

    private void spawnBoss(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String key = (String) obj.get("key");
        if (key == null || key.isEmpty()) {
            return;
        }
        SpawnBossManager.getInstance().spawnNow(key);
        GlobalService.getInstance().chat("Hệ thống", "Web Admin đã triệu hồi boss nhóm " + key + ".");
        Log.info("[WebAdmin] SPAWN_BOSS key=" + key);
    }

    private void serverControl(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String action = (String) obj.get("action");
        if (action == null || action.isEmpty()) {
            action = (String) obj.get("do");
        }
        if (action == null) {
            return;
        }
        switch (action) {
            case "save_all":
                Server.saveAll();
                GlobalService.getInstance().chat("Hệ thống", "Web Admin đã lưu dữ liệu toàn máy chủ.");
                Log.info("[WebAdmin] SERVER_CONTROL save_all done.");
                break;
            case "maintenance":
                Thread t = new Thread(() -> {
                    try {
                        Server.maintance();
                    } catch (Exception e) {
                        Log.error("WebAdmin maintenance err: " + e.getMessage(), e);
                    }
                });
                t.setName("WebAdminMaintenance");
                t.start();
                Log.info("[WebAdmin] SERVER_CONTROL maintenance triggered.");
                break;
            case "reload_event":
                // Đọc lại config.properties rồi khởi tạo lại sự kiện (đổi event không cần restart)
                Config.getInstance().reload();
                Config.getInstance().reloadnjtl();
                Exe_Z.event.Event.init();
                GlobalService.getInstance().chat("Hệ thống", "Web Admin đã cập nhật sự kiện. Sự kiện hiện tại: " + (Exe_Z.event.Event.getEvent() != null ? Exe_Z.event.Event.getEvent().getClass().getSimpleName() : "Tắt"));
                Log.info("[WebAdmin] SERVER_CONTROL reload_event done.");
                break;
            default:
                Log.warn("[WebAdmin] SERVER_CONTROL action không hỗ trợ: " + action);
                break;
        }
    }

    private void close(AutoCloseable c) {
        if (c != null) {
            try {
                c.close();
            } catch (Exception ignored) {
            }
        }
    }
}
