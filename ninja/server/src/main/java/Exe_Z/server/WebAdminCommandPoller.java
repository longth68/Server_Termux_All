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
import java.sql.Timestamp;
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
        updatePlayerStatus();
    }

    /** Snapshot người chơi online vào bảng player_status (Web Admin xem live). */
    private void updatePlayerStatus() {
        Connection conn = null;
        Statement create = null;
        PreparedStatement del = null;
        PreparedStatement ins = null;
        try {
            java.util.List<Char> chars;
            try {
                chars = ServerManager.getChars();
            } catch (Exception e) {
                return;
            }
            if (chars == null) {
                return;
            }
            conn = DbManager.getInstance().getConnection(DbManager.GAME);
            create = conn.createStatement();
            create.executeUpdate("CREATE TABLE IF NOT EXISTS `player_status` ("
                    + "`id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(15) NOT NULL, "
                    + "`level` int(11) NOT NULL DEFAULT 1, `map_id` int(11) NOT NULL DEFAULT 0, "
                    + "`zone_id` int(11) NOT NULL DEFAULT 0, `x` int(11) NOT NULL DEFAULT 0, "
                    + "`y` int(11) NOT NULL DEFAULT 0, `hp` int(11) NOT NULL DEFAULT 0, "
                    + "`max_hp` int(11) NOT NULL DEFAULT 0, `clan` varchar(30) DEFAULT '', "
                    + "`gear` text, `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP, "
                    + "PRIMARY KEY (`id`), UNIQUE KEY `name` (`name`)) "
                    + "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            del = conn.prepareStatement("DELETE FROM `player_status`;");
            del.executeUpdate();
            ins = conn.prepareStatement("INSERT INTO `player_status` "
                    + "(`name`,`level`,`map_id`,`zone_id`,`x`,`y`,`hp`,`max_hp`,`clan`,`gear`,`updated_at`) "
                    + "VALUES (?,?,?,?,?,?,?,?,?,?,NOW());");
            int n = 0;
            for (Char c : chars) {
                if (c == null || c.isCleaned || c.isDead) {
                    continue;
                }
                if (c instanceof Exe_Z.bot.Bot) {
                    continue;
                }
                if (c.user == null || c.user.session == null) {
                    continue;
                }
                try {
                    ins.setString(1, c.name == null ? "" : c.name);
                    ins.setInt(2, c.level);
                    ins.setInt(3, c.mapId);
                    ins.setInt(4, c.zone != null ? c.zone.id : -1);
                    ins.setInt(5, c.x);
                    ins.setInt(6, c.y);
                    ins.setInt(7, c.hp);
                    ins.setInt(8, c.maxHP);
                    String clan = "";
                    try {
                        if (c.clan != null) {
                            clan = String.valueOf(c.clan.getName());
                        }
                    } catch (Exception ignored) {
                    }
                    ins.setString(9, clan);
                    String gear = "{}";
                    try {
                        gear = AutoFarmBot.gearJson(c).toJSONString();
                        if (gear.length() > 60000) {
                            gear = "{}";
                        }
                    } catch (Exception ignored) {
                    }
                    ins.setString(10, gear);
                    ins.addBatch();
                    if (++n >= 200) {
                        break;
                    }
                } catch (Exception ignored) {
                }
            }
            ins.executeBatch();
        } catch (Exception e) {
            Log.error("WebAdminCommandPoller player_status err: " + e.getMessage(), e);
        } finally {
            close(ins);
            close(del);
            close(create);
            close(conn);
        }
    }

    private void updateServerStatus() {
        Connection conn = null;
        Statement create = null;
        PreparedStatement st = null;
        try {
            int online = ServerManager.getNumberOnline();
            int bots = AutoFarmBot.count();
            long mem = (Runtime.getRuntime().totalMemory() - Runtime.getRuntime().freeMemory()) / (1024 * 1024);
            conn = DbManager.getInstance().getConnection(DbManager.GAME);
            create = conn.createStatement();
            create.executeUpdate("CREATE TABLE IF NOT EXISTS `server_status` ("
                    + "`id` int(11) NOT NULL, `online` int(11) NOT NULL DEFAULT 0, "
                    + "`bots` int(11) NOT NULL DEFAULT 0, `memory_mb` bigint(20) NOT NULL DEFAULT 0, "
                    + "`bot_diag` varchar(500) DEFAULT '', "
                    + "`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP, "
                    + "PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            try {
                create.executeUpdate("ALTER TABLE `server_status` ADD COLUMN `bot_diag` varchar(500) DEFAULT '';");
            } catch (Exception ignored) {
            }
            String diag = buildBotDiag();
            st = conn.prepareStatement(
                    "INSERT INTO `server_status` (`id`, `online`, `bots`, `memory_mb`, `bot_diag`, `updated_at`) VALUES (1, ?, ?, ?, ?, NOW()) "
                            + "ON DUPLICATE KEY UPDATE `online` = ?, `bots` = ?, `memory_mb` = ?, `bot_diag` = ?, `updated_at` = NOW();");
            st.setInt(1, online);
            st.setInt(2, bots);
            st.setLong(3, mem);
            st.setString(4, diag);
            st.setInt(5, online);
            st.setInt(6, bots);
            st.setLong(7, mem);
            st.setString(8, diag);
            st.executeUpdate();
        } catch (SQLException e) {
            Log.error("WebAdminCommandPoller status err: " + e.getMessage(), e);
        } finally {
            close(st);
            close(create);
            close(conn);
        }
        updateBotStatus();
    }

    /** Chẩn đoán nhanh trạng thái BOT để hiển thị trên Web Admin (mẫu NRO api/bot_status). */
    private String buildBotDiag() {
        StringBuilder sb = new StringBuilder();
        sb.append("enabled=").append(Exe_Z.bot.ai.BotConfig.ENABLED);
        sb.append(",pop=").append(Exe_Z.bot.ai.BotConfig.POPULATION);
        sb.append(",count=").append(AutoFarmBot.count());
        try {
            java.util.List<Exe_Z.map.Map> maps = Exe_Z.map.MapManager.getInstance().getMaps();
            int nMap = maps == null ? 0 : maps.size();
            int nZone = 0;
            if (maps != null) {
                for (Exe_Z.map.Map m : maps) {
                    if (m != null && m.getZones() != null) {
                        nZone += m.getZones().size();
                    }
                }
            }
            sb.append(",maps=").append(nMap);
            sb.append(",zones=").append(nZone);
        } catch (Exception ignored) {
            sb.append(",maps=?");
        }
        return sb.toString();
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
                    + "`gold` bigint(20) NOT NULL DEFAULT 0, `gender` tinyint(4) NOT NULL DEFAULT 0, "
                    + "`class_id` tinyint(4) NOT NULL DEFAULT 0, `goal` varchar(30) DEFAULT '', "
                    + "`damage` int(11) NOT NULL DEFAULT 0, `friends` int(11) NOT NULL DEFAULT 0, "
                    + "`online_min` int(11) NOT NULL DEFAULT 0, `gear` text, `needs` text, "
                    + "`profile` text, `near` varchar(60) DEFAULT '', "
                    + "`updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP, "
                    + "PRIMARY KEY (`id`), UNIQUE KEY `name` (`name`)) "
                    + "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            // Nâng cấp bảng máy cũ: thêm cột chi tiết kiểu NRO (không dùng IF NOT EXISTS - chỉ MariaDB hỗ trợ)
            String[] alterCols = {
                "ADD COLUMN `gold` bigint(20) NOT NULL DEFAULT 0",
                "ADD COLUMN `gender` tinyint(4) NOT NULL DEFAULT 0",
                "ADD COLUMN `class_id` tinyint(4) NOT NULL DEFAULT 0",
                "ADD COLUMN `goal` varchar(30) DEFAULT ''",
                "ADD COLUMN `damage` int(11) NOT NULL DEFAULT 0",
                "ADD COLUMN `friends` int(11) NOT NULL DEFAULT 0",
                "ADD COLUMN `online_min` int(11) NOT NULL DEFAULT 0",
                "ADD COLUMN `gear` text",
                "ADD COLUMN `needs` text",
                "ADD COLUMN `profile` text",
                "ADD COLUMN `near` varchar(60) DEFAULT ''"
            };
            for (String alter : alterCols) {
                try {
                    create.executeUpdate("ALTER TABLE `bot_status` " + alter + ";");
                } catch (Exception ignored) {
                }
            }
            java.util.List<JSONObject> bots = AutoFarmBot.snapshotInfo(200);
            del = conn.prepareStatement("DELETE FROM `bot_status`;");
            del.executeUpdate();
            if (bots.isEmpty()) {
                return;
            }
            ins = conn.prepareStatement("INSERT INTO `bot_status` "
                    + "(`name`,`level`,`map_id`,`zone_id`,`x`,`y`,`hp`,`max_hp`,`state`,`personality`,`top_need`,"
                    + "`gold`,`gender`,`class_id`,`goal`,`damage`,`friends`,`online_min`,`gear`,"
                    + "`needs`,`profile`,`near`,`updated_at`) "
                    + "VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);");
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
                ins.setLong(12, lng(b.get("gold")));
                ins.setInt(13, num(b.get("gender")));
                ins.setInt(14, num(b.get("class_id")));
                ins.setString(15, str(b.get("goal")));
                ins.setInt(16, num(b.get("damage")));
                ins.setInt(17, num(b.get("friends")));
                ins.setLong(18, lng(b.get("online_min")));
                String gear = "{}";
                try {
                    JSONObject g = AutoFarmBot.snapshotGear(str(b.get("name")));
                    gear = g.toJSONString();
                    if (gear.length() > 60000) {
                        gear = "{}";
                    }
                } catch (Exception ignored) {
                }
                ins.setString(19, gear);
                ins.setString(20, str(b.get("needs")));
                ins.setString(21, str(b.get("profile")));
                ins.setString(22, str(b.get("near")));
                ins.setTimestamp(23, new java.sql.Timestamp(System.currentTimeMillis()));
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

    private void botEdit(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String name = (String) obj.get("name");
        if (name == null || name.isEmpty()) {
            return;
        }
        int level = obj.get("level") != null ? ((Number) obj.get("level")).intValue() : 0;
        int hp = obj.get("hp") != null ? ((Number) obj.get("hp")).intValue() : 0;
        int damage = obj.get("damage") != null ? ((Number) obj.get("damage")).intValue() : 0;
        boolean ok = AutoFarmBot.applyEdit(name, level, hp, damage);
        Log.info("[WebAdmin] BOT_EDIT name=" + name + " lv=" + level + " hp=" + hp + " dmg=" + damage + " ok=" + ok);
        if (ok) {
            GlobalService.getInstance().chat("Hệ thống", "Web Admin đã chỉnh sửa BOT " + name + ".");
        }
    }

    private void gearGive(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String name = (String) obj.get("name");
        int item = obj.get("item") != null ? ((Number) obj.get("item")).intValue() : 0;
        int qty = obj.get("qty") != null ? ((Number) obj.get("qty")).intValue() : 1;
        boolean ok = AutoFarmBot.gearGive(name, item, qty);
        Log.info("[WebAdmin] BOT_GEAR_GIVE name=" + name + " item=" + item + " qty=" + qty + " ok=" + ok);
    }

    private void gearTake(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String name = (String) obj.get("name");
        String place = (String) obj.get("place");
        int slot = obj.get("slot") != null ? ((Number) obj.get("slot")).intValue() : -1;
        boolean ok = AutoFarmBot.gearTake(name, place, slot);
        Log.info("[WebAdmin] BOT_GEAR_TAKE name=" + name + " " + place + "[" + slot + "] ok=" + ok);
    }

    private void gearWear(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String name = (String) obj.get("name");
        int slot = obj.get("slot") != null ? ((Number) obj.get("slot")).intValue() : -1;
        boolean ok = AutoFarmBot.gearWear(name, slot);
        Log.info("[WebAdmin] BOT_GEAR_WEAR name=" + name + " bag[" + slot + "] ok=" + ok);
    }

    private void botTeleport(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        boolean ok = AutoFarmBot.teleportTo((String) obj.get("name"), (String) obj.get("target"));
        Log.info("[WebAdmin] BOT_TELEPORT " + obj.toJSONString() + " ok=" + ok);
    }

    private void botGold(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String name = (String) obj.get("name");
        long amount = obj.get("amount") != null ? ((Number) obj.get("amount")).longValue() : 0;
        boolean ok = AutoFarmBot.addGold(name, amount);
        Log.info("[WebAdmin] BOT_GOLD name=" + name + " amount=" + amount + " ok=" + ok);
    }

    private void botRegear(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        boolean ok = AutoFarmBot.regear((String) obj.get("name"));
        Log.info("[WebAdmin] BOT_REGEAR " + obj.toJSONString() + " ok=" + ok);
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
            case "BOT_EDIT":
                botEdit(data);
                break;
            case "BOT_GEAR_GIVE":
                gearGive(data);
                break;
            case "BOT_GEAR_TAKE":
                gearTake(data);
                break;
            case "BOT_GEAR_WEAR":
                gearWear(data);
                break;
            case "BOT_TELEPORT":
                botTeleport(data);
                break;
            case "BOT_GOLD":
                botGold(data);
                break;
            case "BOT_REGEAR":
                botRegear(data);
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
            case "CHAR_RENAME":
                charRename(data);
                break;
            case "PLAYER_GIVE":
                playerGive(data);
                break;
            case "PLAYER_GEAR_TAKE":
                playerGearTake(data);
                break;
            case "PLAYER_GEAR_WEAR":
                playerGearWear(data);
                break;
            case "PLAYER_EDIT":
                playerEdit(data);
                break;
            case "PLAYER_TASK_SET":
                playerTaskSet(data);
                break;
            case "PLAYER_TASK_FINISH":
                playerTaskFinish(data);
                break;
            case "PLAYER_TASK_RESET":
                playerTaskReset(data);
                break;
            case "PLAYER_GEAR_UPGRADE":
                playerGearUpgrade(data);
                break;
            case "PLAYER_RESET_PW":
                playerResetPassword(data);
                break;
            case "USER_SET_CURRENCY":
                userSetCurrency(data);
                break;
            case "PLAYER_TELEPORT":
                playerTeleport(data);
                break;
            case "UPDATE_SERVER_CONFIG":
                Config.getInstance().reload();
                Config.getInstance().reloadnjtl();
                GlobalService.getInstance().chat("Hệ thống", "Cấu hình máy chủ đã được cập nhật từ Web Admin.");
                Log.info("[WebAdmin] UPDATE_SERVER_CONFIG done.");
                break;
            case "SHOP_RELOAD":
                try {
                    boolean ok = Exe_Z.store.StoreManager.getInstance().Reload();
                    GlobalService.getInstance().chat("Hệ thống", ok ? "Đã tải lại Cửa hàng từ Web Admin." : "Tải lại Cửa hàng lỗi (xem log).");
                    Log.info("[WebAdmin] SHOP_RELOAD done ok=" + ok);
                } catch (Exception e) {
                    Log.error("SHOP_RELOAD err: " + e.getMessage(), e);
                }
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
        if (obj.get("exp_rate") != null) {
            float v = ((Number) obj.get("exp_rate")).floatValue();
            Exe_Z.bot.ai.BotConfig.EXP_RATE = Math.max(0f, Math.min(1f, v));
        }
        if (obj.get("presence_per_player") != null) {
            int v = ((Number) obj.get("presence_per_player")).intValue();
            Exe_Z.bot.ai.BotConfig.PRESENCE_PER_PLAYER = Math.max(0, Math.min(50, v));
        }
        if (obj.get("presence_visit_seconds") != null) {
            int v = ((Number) obj.get("presence_visit_seconds")).intValue();
            Exe_Z.bot.ai.BotConfig.PRESENCE_VISIT_SECONDS = Math.max(30, Math.min(3600, v));
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

    // Đổi tên nhân vật (mẫu NRO renameChar): update DB + live nếu online
    private void charRename(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String oldName = (String) obj.get("old");
        String newName = (String) obj.get("new");
        if (oldName == null || oldName.isEmpty() || newName == null || newName.isEmpty()) {
            return;
        }
        newName = newName.trim();
        if (newName.length() < 3 || newName.length() > 12) {
            Log.warn("[WebAdmin] CHAR_RENAME tên mới không hợp lệ: " + newName);
            return;
        }
        Connection conn = null;
        PreparedStatement st = null;
        ResultSet rs = null;
        try {
            conn = DbManager.getInstance().getConnection(DbManager.GAME);
            st = conn.prepareStatement("SELECT `id` FROM `players` WHERE `name` = ? LIMIT 1;");
            st.setString(1, newName);
            rs = st.executeQuery();
            if (rs.next()) {
                Log.warn("[WebAdmin] CHAR_RENAME trùng tên: " + newName);
                return;
            }
        } catch (SQLException e) {
            Log.error("CHAR_RENAME check err: " + e.getMessage(), e);
            return;
        } finally {
            close(rs);
            close(st);
            close(conn);
        }
        try {
            conn = DbManager.getInstance().getConnection(DbManager.GAME);
            st = conn.prepareStatement("UPDATE `players` SET `name` = ? WHERE `name` = ? LIMIT 1;");
            st.setString(1, newName);
            st.setString(2, oldName);
            if (st.executeUpdate() <= 0) {
                Log.warn("[WebAdmin] CHAR_RENAME không tìm thấy: " + oldName);
                return;
            }
        } catch (SQLException e) {
            Log.error("CHAR_RENAME update err: " + e.getMessage(), e);
            return;
        } finally {
            close(st);
            close(conn);
        }
        Char c = ServerManager.findCharByName(oldName);
        if (c != null) {
            try {
                c.name = newName;
                if (c.zone != null) {
                    c.zone.getService().playerAdd(c);
                }
            } catch (Exception ignored) {
            }
        }
        Log.info("[WebAdmin] CHAR_RENAME " + oldName + " -> " + newName);
        GlobalService.getInstance().chat("Hệ thống", "Web Admin đã đổi tên " + oldName + " thành " + newName + ".");
    }

    // Tặng đồ cho người chơi đang online (mẫu NRO plItemAdd)
    private void playerGive(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String name = (String) obj.get("char");
        int itemId = obj.get("item") != null ? ((Number) obj.get("item")).intValue() : 0;
        int qty = obj.get("qty") != null ? ((Number) obj.get("qty")).intValue() : 1;
        if (name == null || name.isEmpty() || itemId <= 0) {
            return;
        }
        Char c = ServerManager.findCharByName(name);
        if (c == null) {
            Log.warn("[WebAdmin] PLAYER_GIVE người chơi offline: " + name);
            return;
        }
        try {
            Exe_Z.item.ItemTemplate t = Exe_Z.item.ItemManager.getInstance().getItemTemplate(itemId);
            if (t == null) {
                Log.warn("[WebAdmin] PLAYER_GIVE item không tồn tại: " + itemId);
                return;
            }
            Exe_Z.item.Item it;
            if (t.type >= 0 && t.type <= 15) {
                it = Exe_Z.item.ItemFactory.getInstance().newEquipment(itemId);
            } else {
                it = Exe_Z.item.ItemFactory.getInstance().newItem(itemId);
            }
            if (it == null) {
                return;
            }
            try {
                it.setQuantity(Math.max(1, Math.min(qty, 9999)));
            } catch (Exception ignored) {
            }
            if (!c.addItemToBag(it)) {
                Log.warn("[WebAdmin] PLAYER_GIVE túi đầy: " + name);
                return;
            }
            c.updateItemQuantity();
            try {
                c.getService().updateItem();
            } catch (Exception ignored) {
            }
            Log.info("[WebAdmin] PLAYER_GIVE " + name + " item=" + itemId + " qty=" + qty);
        } catch (Exception e) {
            Log.error("PLAYER_GIVE err: " + e.getMessage(), e);
        }
    }

    // Gỡ đồ người chơi online (bag/equip theo slot)
    private void playerGearTake(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String name = (String) obj.get("char");
        if (name == null) {
            name = (String) obj.get("name");
        }
        String place = (String) obj.get("place");
        int slot = obj.get("slot") != null ? ((Number) obj.get("slot")).intValue() : -1;
        Char c = name == null ? null : ServerManager.findCharByName(name);
        boolean ok = false;
        if (c != null) {
            try {
                if ("equip".equalsIgnoreCase(place)) {
                    if (c.equipment != null && slot >= 0 && slot < c.equipment.length
                            && slot <= 9 && c.equipment[slot] != null) {
                        c.equipment[slot] = null;
                        ok = true;
                    }
                } else if (c.bag != null && slot >= 0 && slot < c.bag.length && c.bag[slot] != null) {
                    c.bag[slot] = null;
                    ok = true;
                }
                if (ok) {
                    c.setFashion();
                    c.updateItemQuantity();
                    try {
                        c.getService().updateItem();
                    } catch (Exception ignored) {
                    }
                }
            } catch (Exception e) {
                Log.error("PLAYER_GEAR_TAKE err: " + e.getMessage(), e);
            }
        }
        Log.info("[WebAdmin] PLAYER_GEAR_TAKE " + name + " " + place + "[" + slot + "] ok=" + ok);
    }

    // Mặc đồ từ túi cho người chơi online
    private void playerGearWear(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String name = (String) obj.get("char");
        if (name == null) {
            name = (String) obj.get("name");
        }
        int slot = obj.get("slot") != null ? ((Number) obj.get("slot")).intValue() : -1;
        Char c = name == null ? null : ServerManager.findCharByName(name);
        boolean ok = false;
        if (c != null && c.bag != null && slot >= 0 && slot < c.bag.length) {
            try {
                Exe_Z.item.Item it = c.bag[slot];
                if (it != null && it.template != null && it instanceof Exe_Z.item.Equip) {
                    int type = it.template.type;
                    if (type >= 0 && type <= 9 && c.equipment != null && type < c.equipment.length) {
                        Exe_Z.item.Item old = null;
                        try {
                            old = c.equipment[type];
                        } catch (Exception ignored) {
                        }
                        c.equipment[type] = (Exe_Z.item.Equip) it;
                        c.bag[slot] = old;
                        c.setFashion();
                        c.updateItemQuantity();
                        try {
                            c.getService().updateItem();
                        } catch (Exception ignored) {
                        }
                        ok = true;
                    }
                }
            } catch (Exception e) {
                Log.error("PLAYER_GEAR_WEAR err: " + e.getMessage(), e);
            }
        }
        Log.info("[WebAdmin] PLAYER_GEAR_WEAR " + name + " bag[" + slot + "] ok=" + ok);
    }

    // Chỉnh sửa trực tiếp chỉ số nhân vật (áp dụng cho cả online lẫn offline, cập nhật DB trực tiếp)
    private void playerEdit(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String name = (String) obj.get("char");
        if (name == null) {
            name = (String) obj.get("name");
        }
        if (name == null || name.isEmpty()) {
            Log.warn("[WebAdmin] PLAYER_EDIT thiếu tên nhân vật.");
            return;
        }
        // map field JSON -> (cột DB). DB: level int, exp long, yen long, xu/xuInBox long (Char: coin/coinInBox), spoint int
        String[] fields = {"level", "exp", "yen", "xu", "xuInBox", "spoint", "gender", "class"};
        String[] dbCols  = {"level", "exp",  "yen", "xu", "xuInBox", "spoint", "gender", "class"};
        List<String> sets = new ArrayList<>();
        java.util.List<Object> vals = new ArrayList<>();
        for (int fi = 0; fi < fields.length; fi++) {
            String f = fields[fi];
            if (obj.containsKey(f) && obj.get(f) != null) {
                sets.add("`" + dbCols[fi] + "` = ?");
                try {
                    Number n = (Number) obj.get(f);
                    if (dbCols[fi].equals("level") || dbCols[fi].equals("spoint") || dbCols[fi].equals("gender") || dbCols[fi].equals("class")) {
                        vals.add(n.intValue());
                    } else {
                        vals.add(n.longValue());
                    }
                } catch (Exception e) {
                    try { vals.add(Long.parseLong(strval(obj.get(f)))); } catch (Exception ex) { vals.add(0L); }
                }
            }
        }
        if (sets.isEmpty()) {
            Log.warn("[WebAdmin] PLAYER_EDIT: không có trường nào để cập nhật.");
            return;
        }
        vals.add(name);
        String sql = "UPDATE `players` SET " + String.join(", ", sets) + " WHERE `name` = ?";
        try (Connection con = DbManager.getInstance().getConnection();
             PreparedStatement ps = con.prepareStatement(sql)) {
            for (int i = 0; i < vals.size(); i++) {
                ps.setObject(i + 1, vals.get(i));
            }
            int updated = ps.executeUpdate();
            Char c = Exe_Z.server.ServerManager.findCharByName(name);
            if (c != null) {
                try {
                    for (int fi = 0; fi < fields.length; fi++) {
                        String f = fields[fi];
                        if (!obj.containsKey(f) || obj.get(f) == null) continue;
                        long v;
                        try { v = ((Number) obj.get(f)).longValue(); } catch (Exception e) { continue; }
                        switch (f) {
                            case "level": c.level = (int) v; break;
                            case "exp": c.exp = v; break;
                            case "yen": c.yen = v; break;
                            case "xu": c.coin = v; break;
                            case "xuInBox": c.coinInBox = v; break;
                            case "spoint": c.skillPoint = (short) v; break;
                            case "class": c.classId = (byte) v; break;
                            case "gender": c.gender = (byte) v; break;
                        }
                    }
                    try { c.getService().updateInfoMe(); } catch (Exception ignored) {}
                    try { c.getService().serverMessage("Web Admin vừa cập nhật chỉ số của bạn."); } catch (Exception ignored) {}
                } catch (Exception e) {
                    Log.error("PLAYER_EDIT live sync err: " + e.getMessage(), e);
                }
            }
            Log.info("[WebAdmin] PLAYER_EDIT " + name + " " + sets.size() + " fields, rows=" + updated);
        } catch (SQLException e) {
            Log.error("PLAYER_EDIT err: " + e.getMessage(), e);
        }
    }

    private static String strval(Object o) {
        return o == null ? "" : o.toString();
    }

    // Đặt ID nhiệm vụ chính cho người chơi online
    private void playerTaskSet(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String name = (String) obj.get("char");
        if (name == null) {
            name = (String) obj.get("name");
        }
        int taskId = obj.get("taskId") != null ? ((Number) obj.get("taskId")).intValue() : -1;
        if (taskId < 1) {
            taskId = obj.get("id") != null ? ((Number) obj.get("id")).intValue() : -1;
        }
        Char c = name == null ? null : ServerManager.findCharByName(name);
        if (c == null) {
            Log.warn("[WebAdmin] PLAYER_TASK_SET: không tìm thấy online: " + name);
            return;
        }
        if (taskId < 1) {
            Log.warn("[WebAdmin] PLAYER_TASK_SET: taskId không hợp lệ: " + taskId);
            return;
        }
        try {
            c.taskId = (short) taskId;
            try { c.taskMain = null; } catch (Exception ignored) {}
            try { c.getService().sendTaskInfo(); } catch (Exception ignored) {}
            Log.info("[WebAdmin] PLAYER_TASK_SET " + name + " -> taskId=" + taskId);
        } catch (Exception e) {
            Log.error("PLAYER_TASK_SET err: " + e.getMessage(), e);
        }
    }

    // Hoàn thành ngay nhiệm vụ hiện tại của người chơi online
    private void playerTaskFinish(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String name = (String) obj.get("char");
        if (name == null) {
            name = (String) obj.get("name");
        }
        Char c = name == null ? null : ServerManager.findCharByName(name);
        if (c == null) {
            Log.warn("[WebAdmin] PLAYER_TASK_FINISH: không tìm thấy online: " + name);
            return;
        }
        try {
            c.skipTask();
            try { c.getService().sendTaskInfo(); } catch (Exception ignored) {}
            Log.info("[WebAdmin] PLAYER_TASK_FINISH " + name);
        } catch (Exception e) {
            Log.error("PLAYER_TASK_FINISH err: " + e.getMessage(), e);
        }
    }

    // Reset toàn bộ tiến trình nhiệm vụ (xoá vật phẩm nhiệm vụ + main task về 0)
    private void playerTaskReset(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String name = (String) obj.get("char");
        if (name == null) {
            name = (String) obj.get("name");
        }
        Char c = name == null ? null : ServerManager.findCharByName(name);
        if (c == null) {
            Log.warn("[WebAdmin] PLAYER_TASK_RESET: không tìm thấy online: " + name);
            return;
        }
        try {
            c.taskId = 1;
            try { c.taskMain = null; } catch (Exception ignored) {}
            try { c.removeAllItemTask(); } catch (Exception e) {
                Log.warn("removeAllItemTask err: " + e.getMessage());
            }
            try { c.getService().sendTaskInfo(); } catch (Exception ignored) {}
            Log.info("[WebAdmin] PLAYER_TASK_RESET " + name);
        } catch (Exception e) {
            Log.error("PLAYER_TASK_RESET err: " + e.getMessage(), e);
        }
    }

    // Nâng cấp đồ (đặt upgrade) cho item trong trang bị hoặc túi của người chơi online
    private void playerGearUpgrade(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String name = (String) obj.get("char");
        if (name == null) {
            name = (String) obj.get("name");
        }
        int slot = obj.get("slot") != null ? ((Number) obj.get("slot")).intValue() : -1;
        int up = obj.get("upgrade") != null ? ((Number) obj.get("upgrade")).intValue() : -1;
        String place = (String) obj.get("place");
        Char c = name == null ? null : ServerManager.findCharByName(name);
        if (c == null) {
            Log.warn("[WebAdmin] PLAYER_GEAR_UPGRADE: không tìm thấy online: " + name);
            return;
        }
        if (up < 0 || up > 16) {
            Log.warn("[WebAdmin] PLAYER_GEAR_UPGRADE: upgrade không hợp lệ: " + up);
            return;
        }
        boolean ok = false;
        try {
            if ("equip".equalsIgnoreCase(place)) {
                if (c.equipment != null && slot >= 0 && slot < c.equipment.length && c.equipment[slot] != null) {
                    c.equipment[slot].upgrade = (byte) up;
                    ok = true;
                }
            } else if (c.bag != null && slot >= 0 && slot < c.bag.length && c.bag[slot] != null) {
                c.bag[slot].upgrade = (byte) up;
                ok = true;
            }
            if (ok) {
                c.setFashion();
                c.updateItemQuantity();
                try { c.getService().updateItem(); } catch (Exception ignored) {}
            }
        } catch (Exception e) {
            Log.error("PLAYER_GEAR_UPGRADE err: " + e.getMessage(), e);
        }
        Log.info("[WebAdmin] PLAYER_GEAR_UPGRADE " + name + " " + place + "[" + slot + "] -> +" + up + " ok=" + ok);
    }

    // Reset mật khẩu tài khoản (users.password, BCrypt)
    private void playerResetPassword(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String username = (String) obj.get("username");
        if (username == null) {
            username = (String) obj.get("char");
        }
        String newPw = (String) obj.get("password");
        if (username == null || username.isEmpty() || newPw == null || newPw.length() < 6) {
            Log.warn("[WebAdmin] PLAYER_RESET_PW thiếu username/password (>=6 ký tự).");
            return;
        }
        try (Connection con = DbManager.getInstance().getConnection(DbManager.GAME);
             PreparedStatement ps = con.prepareStatement("UPDATE `users` SET `password` = ? WHERE `username` = ?")) {
            ps.setString(1, Exe_Z.model.Char.passwordHash(newPw));
            ps.setString(2, username);
            int updated = ps.executeUpdate();
            Log.info("[WebAdmin] PLAYER_RESET_PW " + username + " rows=" + updated);
        } catch (SQLException e) {
            Log.error("PLAYER_RESET_PW err: " + e.getMessage(), e);
        }
    }

    // Đặt luong / coin (xu web) cho tài khoản users
    private void userSetCurrency(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String username = (String) obj.get("username");
        if (username == null) {
            username = (String) obj.get("char");
        }
        if (username == null || username.isEmpty()) {
            Log.warn("[WebAdmin] USER_SET_CURRENCY thiếu username.");
            return;
        }
        List<String> sets = new ArrayList<>();
        java.util.List<Object> vals = new ArrayList<>();
        String[] fields = {"luong", "coin", "tongnap"};
        for (String f : fields) {
            if (obj.containsKey(f) && obj.get(f) != null) {
                sets.add("`" + f + "` = ?");
                try { vals.add(((Number) obj.get(f)).longValue()); }
                catch (Exception e) { try { vals.add(Long.parseLong(strval(obj.get(f)))); } catch (Exception ex) { vals.add(0L); } }
            }
        }
        if (sets.isEmpty()) {
            Log.warn("[WebAdmin] USER_SET_CURRENCY không có trường.");
            return;
        }
        vals.add(username);
        String sql = "UPDATE `users` SET " + String.join(", ", sets) + " WHERE `username` = ?";
        try (Connection con = DbManager.getInstance().getConnection(DbManager.GAME);
             PreparedStatement ps = con.prepareStatement(sql)) {
            for (int i = 0; i < vals.size(); i++) {
                ps.setObject(i + 1, vals.get(i));
            }
            int updated = ps.executeUpdate();
            // Đồng bộ Char đang online nếu tài khoản tương ứng đang chơi
            try {
                User u = Exe_Z.server.ServerManager.findUserByUsername(username);
                if (u != null && u.sltChar != null) {
                    if (obj.containsKey("luong")) { try { u.gold = ((Number) obj.get("luong")).intValue(); } catch (Exception ignored) {} }
                    if (obj.containsKey("coin")) { try { u.coin = ((Number) obj.get("coin")).intValue(); } catch (Exception ignored) {} }
                    if (obj.containsKey("tongnap")) { try { u.tongnap = ((Number) obj.get("tongnap")).intValue(); } catch (Exception ignored) {} }
                    try { u.service.updateInfoMe(); } catch (Exception ignored) {}
                }
            } catch (Exception ignored) {}
            Log.info("[WebAdmin] USER_SET_CURRENCY " + username + " rows=" + updated);
        } catch (SQLException e) {
            Log.error("USER_SET_CURRENCY err: " + e.getMessage(), e);
        }
    }

    // Dịch chuyển người chơi online tới map/khu
    private void playerTeleport(String data) throws ParseException {
        JSONObject obj = (JSONObject) parser.parse(data == null ? "{}" : data);
        String name = (String) obj.get("char");
        if (name == null) {
            name = (String) obj.get("name");
        }
        int mapId = obj.get("mapId") != null ? ((Number) obj.get("mapId")).intValue() : -1;
        int zoneId = obj.get("zoneId") != null ? ((Number) obj.get("zoneId")).intValue() : 0;
        Char c = name == null ? null : ServerManager.findCharByName(name);
        if (c == null) {
            Log.warn("[WebAdmin] PLAYER_TELEPORT: không tìm thấy online: " + name);
            return;
        }
        if (mapId < 0) {
            Log.warn("[WebAdmin] PLAYER_TELEPORT: mapId không hợp lệ.");
            return;
        }
        try {
            c.outZone();
            c.joinZone(mapId, zoneId, -1);
            if (c.zone != null) {
                c.setXY((short) 100, (short) 100);
                c.zone.getService().playerMove(c);
            }
            Log.info("[WebAdmin] PLAYER_TELEPORT " + name + " -> map " + mapId + " khu " + zoneId);
        } catch (Exception e) {
            Log.error("PLAYER_TELEPORT err: " + e.getMessage(), e);
        }
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
