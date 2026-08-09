package Exe_Z.server;

import Exe_Z.ability.AbilityCustom;
import Exe_Z.bot.Bot;
import Exe_Z.bot.attack.AttackAround;
import Exe_Z.bot.move.botMovefull;
import Exe_Z.constants.MapName;
import Exe_Z.constants.SkillName;
import Exe_Z.convert.Converter;
import Exe_Z.fashion.FashionCustom;
import Exe_Z.map.Map;
import Exe_Z.map.MapManager;
import Exe_Z.map.zones.Zone;
import Exe_Z.server.GameData;
import Exe_Z.util.NinjaUtils;

import javax.swing.*;
import java.awt.event.ActionEvent;
import java.util.List;

public class JFrameSendGoiBot extends JFrame {

    private JTextField soluong;
    private JLabel jLabel1;
    private JLabel jLabel2;
    private JLabel jLabel3;
    private JLabel jLabel4;
    private JLabel jLabel5;
    private JLabel jLabel6;
    private JLabel jLabel7;
    private JLabel jLabel8;
    private JButton xacnhan;
    private JButton huongDan;
    private JTextField name;
    private JTextField mapget;
    private JTextField Daubot;
    private JTextField Thanbot;
    private JTextField Chanbot;
    private JTextField Levelbot;
    private JTextField Khubot;
    private JFrame controlPanel;
    private JRadioButton BotMove;
    private JRadioButton BotLv;
    private JRadioButton BotNv;
    private ButtonGroup topGroup;

    public JFrameSendGoiBot() {
        initComponents();
    }

    private void initComponents() {
        // Khởi tạo các nhãn, trường văn bản và nút
        jLabel1 = new JLabel("Tên bot");
        jLabel2 = new JLabel("Nhập Số bot");
        jLabel3 = new JLabel("Nhập ID MAP");
        jLabel4 = new JLabel("Nhập PART ĐẦU");
        jLabel5 = new JLabel("Nhập PART THÂN");
        jLabel6 = new JLabel("Nhập PART CHÂN");
        jLabel7 = new JLabel("Nhập LEVEL BOT");
        jLabel8 = new JLabel("Nhập KHU BOT");

        soluong = new JTextField("23");
        Daubot = new JTextField("276");
        Thanbot = new JTextField("277");
        Chanbot = new JTextField("278");
        Levelbot = new JTextField("10");
        Khubot = new JTextField("0");
        name = new JTextField();
        mapget = new JTextField();
        xacnhan = new JButton("Xác nhận");
        huongDan = new JButton("Xem hướng dẫn");

        BotMove = new JRadioButton("Bot Move");
        BotLv = new JRadioButton("Bot Level");
        BotNv = new JRadioButton("Bot Nhiệm vụ");
        topGroup = new ButtonGroup();
        topGroup.add(BotMove);
        topGroup.add(BotLv);
        topGroup.add(BotNv);

        BotMove.setSelected(true); // Chọn mặc định

        xacnhan.addActionListener(this::xacnhanActionPerformed);
        huongDan.addActionListener(evt -> openControlPanel());

        // Cấu hình layout
        GroupLayout layout = new GroupLayout(getContentPane());
        getContentPane().setLayout(layout);
        layout.setAutoCreateGaps(true);
        layout.setAutoCreateContainerGaps(true);

        layout.setHorizontalGroup(
                layout.createParallelGroup(GroupLayout.Alignment.LEADING)
                        .addGroup(layout.createSequentialGroup()
                                .addGap(30)
                                .addGroup(layout.createParallelGroup(GroupLayout.Alignment.LEADING)
                                        .addComponent(jLabel1)
                                        .addComponent(jLabel2)
                                        .addComponent(jLabel3)
                                        .addComponent(jLabel4)
                                        .addComponent(jLabel5)
                                        .addComponent(jLabel6)
                                        .addComponent(jLabel7)
                                        .addComponent(jLabel8))
                                .addGap(18)
                                .addGroup(layout.createParallelGroup(GroupLayout.Alignment.LEADING, false)
                                        .addComponent(soluong)
                                        .addComponent(mapget)
                                        .addComponent(Daubot)
                                        .addComponent(Thanbot)
                                        .addComponent(Chanbot)
                                        .addComponent(Levelbot)
                                        .addComponent(Khubot)
                                        .addComponent(name, GroupLayout.DEFAULT_SIZE, 200, Short.MAX_VALUE))
                                .addContainerGap(30, Short.MAX_VALUE))
                        .addGroup(layout.createSequentialGroup()
                                .addGap(30)
                                .addGroup(layout.createParallelGroup(GroupLayout.Alignment.LEADING)
                                        .addComponent(BotMove)
                                        .addComponent(BotLv)
                                        .addComponent(BotNv))
                                .addContainerGap(GroupLayout.DEFAULT_SIZE, Short.MAX_VALUE))
                        .addGroup(layout.createSequentialGroup()
                                .addGap(90)
                                .addComponent(xacnhan)
                                .addPreferredGap(LayoutStyle.ComponentPlacement.UNRELATED)
                                .addComponent(huongDan)
                                .addContainerGap(GroupLayout.DEFAULT_SIZE, Short.MAX_VALUE))
        );

        layout.setVerticalGroup(
                layout.createSequentialGroup()
                        .addGap(30)
                        .addGroup(layout.createParallelGroup(GroupLayout.Alignment.BASELINE)
                                .addComponent(jLabel1)
                                .addComponent(name, GroupLayout.PREFERRED_SIZE, GroupLayout.DEFAULT_SIZE, GroupLayout.PREFERRED_SIZE))
                        .addGap(18)
                        .addGroup(layout.createParallelGroup(GroupLayout.Alignment.BASELINE)
                                .addComponent(jLabel2)
                                .addComponent(soluong, GroupLayout.PREFERRED_SIZE, GroupLayout.DEFAULT_SIZE, GroupLayout.PREFERRED_SIZE))
                        .addGap(18)
                        .addGroup(layout.createParallelGroup(GroupLayout.Alignment.BASELINE)
                                .addComponent(jLabel3)
                                .addComponent(mapget, GroupLayout.PREFERRED_SIZE, GroupLayout.DEFAULT_SIZE, GroupLayout.PREFERRED_SIZE))
                        .addGap(18)
                        .addGroup(layout.createParallelGroup(GroupLayout.Alignment.BASELINE)
                                .addComponent(jLabel4)
                                .addComponent(Daubot, GroupLayout.PREFERRED_SIZE, GroupLayout.DEFAULT_SIZE, GroupLayout.PREFERRED_SIZE))
                        .addGap(18)
                        .addGroup(layout.createParallelGroup(GroupLayout.Alignment.BASELINE)
                                .addComponent(jLabel5)
                                .addComponent(Thanbot, GroupLayout.PREFERRED_SIZE, GroupLayout.DEFAULT_SIZE, GroupLayout.PREFERRED_SIZE))
                        .addGap(18)
                        .addGroup(layout.createParallelGroup(GroupLayout.Alignment.BASELINE)
                                .addComponent(jLabel6)
                                .addComponent(Chanbot, GroupLayout.PREFERRED_SIZE, GroupLayout.DEFAULT_SIZE, GroupLayout.PREFERRED_SIZE))
                        .addGap(18)
                        .addGroup(layout.createParallelGroup(GroupLayout.Alignment.BASELINE)
                                .addComponent(jLabel7)
                                .addComponent(Levelbot, GroupLayout.PREFERRED_SIZE, GroupLayout.DEFAULT_SIZE, GroupLayout.PREFERRED_SIZE))
                        .addGap(18)
                        .addGroup(layout.createParallelGroup(GroupLayout.Alignment.BASELINE)
                                .addComponent(jLabel8)
                                .addComponent(Khubot, GroupLayout.PREFERRED_SIZE, GroupLayout.DEFAULT_SIZE, GroupLayout.PREFERRED_SIZE))
                        .addGap(18)
                        .addGroup(layout.createSequentialGroup()
                                .addComponent(BotMove)
                                .addGap(10)
                                .addComponent(BotLv)
                                .addGap(10)
                                .addComponent(BotNv))
                        .addGap(30)
                        .addGroup(layout.createParallelGroup(GroupLayout.Alignment.BASELINE)
                                .addComponent(xacnhan)
                                .addComponent(huongDan))
                        .addContainerGap(30, Short.MAX_VALUE)
        );

        pack();
        setDefaultCloseOperation(WindowConstants.DISPOSE_ON_CLOSE);
        setTitle("Gọi Bot");
    }

    private void xacnhanActionPerformed(ActionEvent evt) {
        if (name.getText().isEmpty() || soluong.getText().isEmpty() || mapget.getText().isEmpty() || (!BotMove.isSelected() && !BotLv.isSelected() && !BotNv.isSelected())) {
            JOptionPane.showMessageDialog(rootPane, "Vui lòng nhập đủ các trường!");
            return;
        }

        if (!checkNumber(soluong.getText()) || !checkNumber(mapget.getText())
                || !checkNumber(Daubot.getText()) || !checkNumber(Thanbot.getText())
                || !checkNumber(Chanbot.getText()) || !checkNumber(Levelbot.getText()) || !checkNumber(Khubot.getText())) {
            JOptionPane.showMessageDialog(rootPane, "Sai định dạng!");
            return;
        }
        if (BotMove.isSelected()) {
            int botCount = Integer.parseInt(soluong.getText());
            int mapID = Integer.parseInt(mapget.getText());
            int DauBot = Integer.parseInt(Daubot.getText());
            int ThanBot = Integer.parseInt(Thanbot.getText());
            int ChanBot = Integer.parseInt(Chanbot.getText());
            int LevelBot = Integer.parseInt(Levelbot.getText());
            int KhuBot = Integer.parseInt(Khubot.getText());
            createBotMv(botCount, mapID, DauBot, ThanBot, ChanBot, LevelBot, KhuBot);
        } else if (BotLv.isSelected()) {
            int botCount = Integer.parseInt(soluong.getText());
            int mapID = Integer.parseInt(mapget.getText());
            int DauBot = Integer.parseInt(Daubot.getText());
            int ThanBot = Integer.parseInt(Thanbot.getText());
            int ChanBot = Integer.parseInt(Chanbot.getText());
            int LevelBot = Integer.parseInt(Levelbot.getText());
            int KhuBot = Integer.parseInt(Khubot.getText());
            createBotLv(botCount, mapID, DauBot, ThanBot, ChanBot, LevelBot, KhuBot);
        } else if (BotNv.isSelected()) {
            int botCount = Integer.parseInt(soluong.getText());
            int mapID = Integer.parseInt(mapget.getText());
            int DauBot = Integer.parseInt(Daubot.getText());
            int ThanBot = Integer.parseInt(Thanbot.getText());
            int ChanBot = Integer.parseInt(Chanbot.getText());
            int LevelBot = Integer.parseInt(Levelbot.getText());
            int KhuBot = Integer.parseInt(Khubot.getText());
            createBotNv(botCount, mapID, DauBot, ThanBot, ChanBot, LevelBot, KhuBot);
        }

    }

    private void createBotMv(int botCount, int mapID, int DauBot, int ThanBot, int ChanBot, int LevelBot, int KhuBot) {
        Map map = MapManager.getInstance().find(mapID);
        Zone zone = map.getZoneById(KhuBot);
        List<Zone> zones = map.getZones();
        if (zones.get(KhuBot).getNumberChar() + botCount >= 24) {
            JOptionPane.showMessageDialog(rootPane, "Khu vực đã đầy!");
            return;
        }
        JOptionPane.showMessageDialog(rootPane, botCount + " bot move đã được tạo thành công trên bản đồ ID " + mapID + "!");
        for (int i = 0; i < botCount; i++) {
            Bot bot = Bot.builder().id(1000 - i).name(name.getText() + i)
                    .level(LevelBot)
                    .classId((byte) NinjaUtils.nextInt(1, 6))
                    .build();
            bot.setDefault();
            FashionCustom fashionCustom = FashionCustom.builder()
                    .head((short) DauBot)
                    .body((short) ThanBot)
                    .leg((short) ChanBot)
                    .weapon((short) -1)
                    .build();
            bot.setFashionStrategy(fashionCustom);
            AbilityCustom abilityCustom = AbilityCustom.builder()
                    .hp(NinjaUtils.nextInt(500, 999) * LevelBot)
                    .mp(NinjaUtils.nextInt(500, 999) * LevelBot)
                    .damage(NinjaUtils.nextInt(999, 1999) * LevelBot)
                    .damage2(NinjaUtils.nextInt(1999, 2999) * LevelBot)
                    .miss(NinjaUtils.nextInt(10, 50) * LevelBot)
                    .exactly(NinjaUtils.nextInt(10, 50) * LevelBot)
                    .fatal(NinjaUtils.nextInt(10, 50) * LevelBot)
                    .build();
            bot.setAbilityStrategy(abilityCustom);
            bot.setMove(new botMovefull());
            AttackAround attackAround = new AttackAround();
            attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_ENKO_BAKUSATSU, 12)));
            attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_RAIJIN, 12)));
            attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_SHABONDAMA, 12)));
            attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_KOGORASERU, 12)));
            attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_TSUMABENI, 12)));
            attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_KAMIKAZE, 12)));
            bot.setAttack(attackAround);
            bot.setAbility();
            bot.setFashion();
            bot.recovery();
            int mapWidth = zone.tilemap.pxw;
            int mapHeight = zone.tilemap.pxh;
            short randomX = (short) NinjaUtils.nextInt(50, mapWidth - 50);
            short randomY = (short) NinjaUtils.nextInt(50, mapHeight - 50);
            randomY = zone.tilemap.collisionY(randomX, randomY);
            bot.setXY(randomX, randomY);
            zone.join(bot);
        }
    }

    private void createBotLv(int botCount, int mapID, int DauBot, int ThanBot, int ChanBot, int LevelBot, int KhuBot) {
        Map map = MapManager.getInstance().find(mapID);
        Zone zone = map.getZoneById(KhuBot);
        List<Zone> zones = map.getZones();
        if (zones.get(KhuBot).getNumberChar() >= 24) {
            JOptionPane.showMessageDialog(rootPane, "Khu vực đã đầy!");
            return;
        }
        JOptionPane.showMessageDialog(rootPane, botCount + " bot up Level đã được tạo thành công trên bản đồ ID " + mapID + "!");
        for (int i = 0; i < botCount; i++) {
            Bot bot = Bot.builder().id(1000 - i).name(name.getText() + i)
                    .level(LevelBot)
                    .classId((byte) NinjaUtils.nextInt(1, 6))
                    .build();
            bot.setDefault();
            FashionCustom fashionCustom = FashionCustom.builder()
                    .head((short) DauBot)
                    .body((short) ThanBot)
                    .leg((short) ChanBot)
                    .weapon((short) -1)
                    .build();
            bot.setFashionStrategy(fashionCustom);

            AbilityCustom abilityCustom = AbilityCustom.builder()
                    .hp(NinjaUtils.nextInt(500, 999) * LevelBot)
                    .mp(NinjaUtils.nextInt(500, 999) * LevelBot)
                    .damage(NinjaUtils.nextInt(999, 1999) * LevelBot)
                    .damage2(NinjaUtils.nextInt(1999, 2999) * LevelBot)
                    .miss(NinjaUtils.nextInt(10, 50) * LevelBot)
                    .exactly(NinjaUtils.nextInt(10, 50) * LevelBot)
                    .fatal(NinjaUtils.nextInt(10, 50) * LevelBot)
                    .build();
            bot.setAbilityStrategy(abilityCustom);
            bot.setMove(new botMovefull());
            AttackAround attackAround = new AttackAround();
            attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_ENKO_BAKUSATSU, 12)));
            attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_RAIJIN, 12)));
            attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_SHABONDAMA, 12)));
            attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_KOGORASERU, 12)));
            attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_TSUMABENI, 12)));
            attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_KAMIKAZE, 12)));
            bot.setAttack(attackAround);
            bot.setAbility();
            bot.setFashion();
            bot.recovery();
            bot.setXY((short) (1000 + i * 50), (short) (240 + i * 50));
            zone.join(bot);
        }
    }

    private void createBotNv(int botCount, int mapID, int DauBot, int ThanBot, int ChanBot, int LevelBot, int KhuBot) {
        Map map = MapManager.getInstance().find(mapID);
        Zone zone = map.getZoneById(KhuBot);
        List<Zone> zones = map.getZones();
        if (zones.get(KhuBot).getNumberChar() >= 24) {
            JOptionPane.showMessageDialog(rootPane, "Khu vực đã đầy!");
            return;
        }
        JOptionPane.showMessageDialog(rootPane, botCount + " bot Nhiệm Vụ đã được tạo thành công trên bản đồ ID " + mapID + "!");
        for (int i = 0; i < botCount; i++) {
            Bot bot = Bot.builder().id(1000 - i).name(name.getText() + i)
                    .level(LevelBot)
                    .classId((byte) NinjaUtils.nextInt(1, 6))
                    .build();
            bot.setDefault();
            FashionCustom fashionCustom = FashionCustom.builder()
                    .head((short) DauBot)
                    .body((short) ThanBot)
                    .leg((short) ChanBot)
                    .weapon((short) -1)
                    .build();
            bot.setFashionStrategy(fashionCustom);

            AbilityCustom abilityCustom = AbilityCustom.builder()
                    .hp(NinjaUtils.nextInt(500, 999) * LevelBot)
                    .mp(NinjaUtils.nextInt(500, 999) * LevelBot)
                    .damage(NinjaUtils.nextInt(999, 1999) * LevelBot)
                    .damage2(NinjaUtils.nextInt(1999, 2999) * LevelBot)
                    .miss(NinjaUtils.nextInt(10, 50) * LevelBot)
                    .exactly(NinjaUtils.nextInt(10, 50) * LevelBot)
                    .fatal(NinjaUtils.nextInt(10, 50) * LevelBot)
                    .build();
            bot.setAbilityStrategy(abilityCustom);
            bot.setMove(new botMovefull());
            AttackAround attackAround = new AttackAround();
            attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_ENKO_BAKUSATSU, 12)));
            attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_RAIJIN, 12)));
            attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_SHABONDAMA, 12)));
            attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_KOGORASERU, 12)));
            attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_TSUMABENI, 12)));
            attackAround.addSkill(Converter.getInstance().newSkill(GameData.getInstance().getSkill(SkillName.CHIEU_KAMIKAZE, 12)));
            bot.setAttack(attackAround);
            bot.setAbility();
            bot.setFashion();
            bot.recovery();
            bot.setXY((short) (1000 + i * 50), (short) (240 + i * 50));
            zone.join(bot);
        }
    }

    private void openControlPanel() {
        if (controlPanel == null) {
            controlPanel = new JFrame("Hướng Dẫn");
            JLabel ghiChuLabel = new JLabel("<html>Nhập hướng dẫn cụ thể cho các loại bot.<br></html>");
            controlPanel.add(ghiChuLabel);
            controlPanel.setSize(300, 200);
            controlPanel.setLocationRelativeTo(null);
        }
        controlPanel.setVisible(true);
    }

    public static boolean checkNumber(String str) {
        return str.matches("\\d+");
    }

    public static void run() {
        SwingUtilities.invokeLater(() -> new JFrameSendGoiBot().setVisible(true));
    }
}
