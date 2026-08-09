package Exe_Z.bot;

import Exe_Z.ability.AbilityCustom;
import Exe_Z.bot.attack.AttackAround;
import Exe_Z.constants.CMDConfirmPopup;
import Exe_Z.constants.CMDMenu;
import Exe_Z.constants.SkillName;
import Exe_Z.convert.Converter;
import Exe_Z.db.jdbc.DbManager;
import Exe_Z.fashion.FashionCustom;
import Exe_Z.model.Char;
import Exe_Z.model.ConfirmPopup;
import Exe_Z.model.Menu;
import Exe_Z.server.GameData;
import Exe_Z.server.ServerManager;
import Exe_Z.util.Log;
import Exe_Z.util.NinjaUtils;
import Exe_Z.util.TimeUtils;
import lombok.Setter;
import org.json.JSONException;
import org.json.simple.JSONObject;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.util.*;

public class Assassin extends Bot {

    public static HashMap<String, List<Assassin>> assassins = null;
    private static int idBase = -9000000;
    private int head;
    private int body;
    private int leg;
    private long lastTimeChat;
    public int luong_thue;
    public String name_atk;
    @Setter
    private Char target;

    static String[] chats = new String[]{
            "Ta được thuê đến đây để giết ngươi!",
            "Vì tiền ta có thể làm tất cả.",
            "Nhiệm vụ của ta là mang đầu của ngươi về!"
    };

    public Assassin(int id) {
        super(id);
    }

    public Assassin(int id, String name, int head, int body, int leg) {
        super(id, name, 150, Char.PK_DOSAT, (byte) 0);
        this.head = head;
        this.body = body;
        this.leg = leg;
    }

    @Override
    public boolean isAssassin() {
        return true;
    }

    public static Assassin createAssassin(int price) {
        String name = "";
        int dau = 0;
        int than = 0;
        int chan = 0;
        int hp = 0;
        int mp = 0;
        int dame = 0;
        int exactly = 0;
        int fatal = 0;
        int miss = 0;
        switch (price) {
            case 1000000000:
                name = "Sát thủ thường";
                dau = 25;
                than = 180;
                chan = 181;
                hp = 1000000;
                mp = 2000000000;
                dame = 50000;
                exactly = 1000;
                fatal = 32000;
                miss = 2000;
                break;
            case 1200000000:
                name = "Sát thủ VIP";
                dau = 318;
                than = 300;
                chan = 301;
                hp = 5000000;
                mp = 2000000000;
                dame = 150000;
                exactly = 2000;
                fatal = 32000;
                miss = 5000;
                break;
            case 1300000000:
                name = "Sát thủ siêu VIP";
                dau = 320;
                than = 298;
                chan = 299;
                hp = 10000000;
                mp = 2000000000;
                dame = 250000;
                exactly = 5000;
                fatal = 32000;
                miss = 10000;
                break;
            case 1500000000:
                name = "Sát thủ VIP PRO";
                dau = 148;
                than = 302;
                chan = 303;
                hp = 50000000;
                mp = 2000000000;
                dame = 500000;
                exactly = 15000;
                fatal = 32000;
                miss = 20000;
                break;
        }

        Assassin bot = new Assassin(idBase--, name, 148, 302, 303);
        bot.luong_thue = price;
        bot.setDefault();
        FashionCustom fashionCustom = FashionCustom.builder()
                .head((short) dau)
                .body((short) than)
                .leg((short) chan)
                .fMask((short) 54)
                .build();
        bot.setFashionStrategy(fashionCustom);
        AbilityCustom abilityCustom = AbilityCustom.builder()
                .hp(hp)
                .mp(mp)
                .damage(dame)
                .damage2(dame)
                .exactly(exactly)
                .fatal(fatal)
                .miss(miss)
                .build();
        bot.setAbilityStrategy(abilityCustom);
        bot.setAttack();
        bot.setAbility();
        bot.setFashion();
        bot.recovery();
        return bot;
    }

    public void setAttack() {
        AttackAround attackAround = new AttackAround();
        attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_ENKO_BAKUSATSU, 12)));
        attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_RAIJIN, 12)));
        attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_SHABONDAMA, 12)));
        attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_KOGORASERU, 12)));
        attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_TSUMABENI, 12)));
        attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_KAMIKAZE, 12)));
        setAttack(attackAround);
    }

    @Override
    public void setDefault() {
        super.setDefault();
        FashionCustom fashionCustom = FashionCustom.builder()
                .head((short) head)
                .body((short) body)
                .leg((short) leg)
                .weapon((short) -1)
                .build();
        setFashionStrategy(fashionCustom);
        AbilityCustom abilityCustom = AbilityCustom.builder()
                .hp(2000000)
                .build();
        setAbilityStrategy(abilityCustom);
        setAbility();
        setFashion();
    }

    @Override
    public void updateEveryHalfSecond() {
        super.updateEveryHalfSecond();
        long now = System.currentTimeMillis();
        if (TimeUtils.canDoWithTime(lastTimeChat, 5000)) {
            lastTimeChat = now;
            zone.getService().chat(this.id, (String) NinjaUtils.randomObject(chats));
        }
        Char char_target = Char.findCharByName(target.name);
        if (char_target != null && char_target.zone.map.id != this.zone.map.id) {
            this.zone.out(this);
            char_target.zone.join(this);
            this.x = char_target.x;
            this.y = char_target.y;
        } else if (char_target == null) {
            this.zone.out(this);
        }
    }

    public static void thueSatThu(Char _char, String name_target) {
        _char.tempName = name_target;
        final Char player = ServerManager.findCharByName(_char.tempName);
        if (player == null) {
            _char.serverDialog("Không tìm thấy người chơi này!");
            return;
        }
        _char.menus.add(new Menu(CMDMenu.EXECUTE, "Sát thủ thường", () -> {
            _char.tempIndex = -1000;
            _char.setConfirmPopup(new ConfirmPopup(CMDConfirmPopup.THUE_SAT_THU,
                    "Bạn có muốn thuê sát thủ thường truy sát " + name_target + "  và nhận 1 điểm thuê sát thủ không?"));
            _char.diemsatthu +=1;
            _char.getService().openUIConfirmID();
        }));
        _char.menus.add(new Menu(CMDMenu.EXECUTE, "Sát thủ VIP", () -> {
            _char.tempIndex = -1001;
            _char.setConfirmPopup(new ConfirmPopup(CMDConfirmPopup.THUE_SAT_THU,
                    "Bạn có muốn thuê sát thủ VIP truy sát " + name_target + "  và nhận 2 điểm thuê sát thủ không?"));
            _char.diemsatthu +=2;
            _char.getService().openUIConfirmID();
        }));
        _char.menus.add(new Menu(CMDMenu.EXECUTE, "Sát thủ siêu VIP", () -> {
            _char.tempIndex = -1002;
            _char.setConfirmPopup(new ConfirmPopup(CMDConfirmPopup.THUE_SAT_THU,
                    "Bạn có muốn thuê sát thủ siêu VIP truy sát " + name_target + "  và nhận 5 điểm thuê sát thủ không?"));
            _char.diemsatthu +=5;
            _char.getService().openUIConfirmID();
        }));
        _char.menus.add(new Menu(CMDMenu.EXECUTE, "Sát thủ VIP PRO", () -> {
            _char.tempIndex = -1003;
            _char.setConfirmPopup(new ConfirmPopup(CMDConfirmPopup.THUE_SAT_THU,
                    "Bạn có muốn thuê sát thủ VIP PRO truy sát " + name_target + "  và nhận 10 điểm thuê sát thủ không?"));
            _char.diemsatthu +=10;
            _char.getService().openUIConfirmID();
        }));
        _char.getService().openUIMenu();
    }

    public static void removeAssassin(String key, Assassin value) {
        List<Assassin> assassinList = assassins.get(key);
        if (assassinList != null) {
            assassinList.removeIf(as -> as.id == value.id);
        }
    }

    public static void saveAssassin() {
        try {
            if (assassins == null) return;
            JSONObject data = new JSONObject();

            for (Map.Entry<String, List<Assassin>> entry : assassins.entrySet()) {
                if (entry.getValue().isEmpty()) continue;
                StringJoiner joiner = new StringJoiner(",");
                for (Assassin assassin : entry.getValue()) {
                    joiner.add(String.valueOf(assassin.luong_thue));
                }
                data.put(entry.getKey(), joiner.toString());
            }

            Connection conn = DbManager.getInstance().getConnection(DbManager.SAVE_DATA);
            PreparedStatement stmt = conn.prepareStatement("UPDATE `others` SET `value` = ? WHERE `id` = ? LIMIT 1");
            try {
                stmt.setString(1, data.toJSONString());
                stmt.setInt(2, 10);
                stmt.executeUpdate();
            } finally {
                stmt.close();
            }
        } catch (Exception e) {
            Log.error("saveData Assassin ex: " + e.getMessage(), e);
        }
    }

    public static void load(String data) throws JSONException {
        assassins = new HashMap<>();
        org.json.JSONObject jsonObject = new org.json.JSONObject(data);

        for (Iterator it = jsonObject.keys(); it.hasNext(); ) {
            String key = (String) it.next();
            String valuesString = jsonObject.getString(key);
            String[] valuesArray = valuesString.split(",");
            List<Assassin> values = new ArrayList<>();
            for (String s : valuesArray) {
                Assassin assassin = Assassin.createAssassin(Integer.parseInt(s));
                assassin.name_atk = key;
                values.add(assassin);
            }
            assassins.put(key, values);
        }
    }
}
