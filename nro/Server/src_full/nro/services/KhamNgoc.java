package nro.services;

import consts.ConstNpc;
import jbcd.ConnectDB;
import java.io.IOException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.ArrayList;
import java.util.List;
import models.Item.Item;
import models.Item.ItemOption;
import models.Item.ItemService;
import network.io.Message;
import nro.inventory.InventoryService;
import nro.player.Player;
import nro.template.ItemOptionTemplate;
import org.json.simple.JSONArray;
import org.json.simple.JSONObject;
import org.json.simple.JSONValue;
import Utils.Logger;

/**
 * @author Hoàng Việt - 0857853150
 */
public class KhamNgoc {

    public int id;

    private static final byte START = 0;
    public final List<KhamNgocTemplate> khamNgocTemplates = new ArrayList<>();
    public static final List<KhamNgoc> KHAM_NGOC = new ArrayList<>();
    private static KhamNgoc i;

    public static KhamNgoc gI() {
        if (i == null) {
            i = new KhamNgoc();
        }
        return i;
    }

    /**
     * Seed mặc định cho người chơi mới / dữ liệu rỗng:
     * mọi nhóm ngọc trong KHAM_NGOC đều ở level -1 (chưa kích hoạt).
     */
    public void seedDefaultKhamNgoc(Player pl) {
        if (pl.khamNgoc.isEmpty()) {
            for (KhamNgoc k : KHAM_NGOC) {
                KhamNgocPlayer knp = new KhamNgocPlayer();
                knp.idNro = k.id;
                knp.levelNro = -1;
                pl.khamNgoc.add(knp);
            }
        }
    }

    public void loadKhamNgoc() {
        try (Connection con = ConnectDB.getConnection();
             PreparedStatement ps = con.prepareStatement("SELECT * FROM `kham_ngoc`");
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                KhamNgoc khamngoc = new KhamNgoc();
                khamngoc.id = rs.getInt("id");
                JSONArray jArr = (JSONArray) JSONValue.parse(rs.getString("options"));
                if (jArr == null) {
                    continue;
                }
                for (int i = 0; i < jArr.size(); i++) {
                    JSONObject obj = (JSONObject) jArr.get(i);
                    KhamNgocTemplate khamngocOptions = new KhamNgocTemplate();
                    khamngocOptions.level = Integer.parseInt(String.valueOf(obj.get("level")));
                    khamngocOptions.tempId = Integer.parseInt(String.valueOf(obj.get("tempid")));
                    khamngocOptions.max_value = Integer.parseInt(String.valueOf(obj.get("max_value")));
                    int oID = Integer.parseInt(String.valueOf(obj.get("id")));
                    int oParam = Integer.parseInt(String.valueOf(obj.get("param")));
                    ItemOption option = new ItemOption(oID, oParam);
                    if (option.optionTemplate == null) {
                        option.optionTemplate = new ItemOptionTemplate(oID, "", 0);
                    }
                    option.param = oParam;
                    khamngocOptions.options = option;
                    khamngoc.khamNgocTemplates.add(khamngocOptions);
                }
                KHAM_NGOC.add(khamngoc);
            }
            Logger.success("Load Kham Ngoc thanh cong (" + KHAM_NGOC.size() + ")");
        } catch (Exception ex) {
            Logger.logException(KhamNgoc.class, ex);
        }
    }

    public void Send_KhamNgocTemplate(Player pl) {
        Message msg = null;
        try {
            msg = new Message(108);
            msg.writer().writeByte(0);
            msg.writer().writeByte(START);
            msg.writer().writeByte(KHAM_NGOC.size());

            for (int j = 0; j < KHAM_NGOC.size(); j++) {
                KhamNgoc manager = KHAM_NGOC.get(j);
                msg.writer().writeInt(manager.id);
                msg.writer().writeByte(manager.khamNgocTemplates.size());
                for (int k = 0; k < manager.khamNgocTemplates.size(); k++) {
                    KhamNgocTemplate template = manager.khamNgocTemplates.get(k);
                    msg.writer().writeInt(template.level);
                    msg.writer().writeInt(template.tempId);
                    msg.writer().writeInt(template.max_value);
                    msg.writer().writeInt(template.options.optionTemplate.id);
                    msg.writer().writeInt(template.options.param);
                }
            }
            pl.sendMessage(msg);
            msg.cleanup();
        } catch (IOException e) {
        } finally {
            if (msg != null) {
                msg.cleanup();
            }
        }
    }

    public void Send_KhamNgoc_Player(Player pl) {
        Message msg = null;
        try {
            msg = new Message(108);
            msg.writer().writeByte(1);
            msg.writer().writeByte(pl.active_kham_ngoc);
            msg.writer().writeByte(pl.khamNgoc.size());

            for (int j = 0; j < pl.khamNgoc.size(); j++) {
                KhamNgocPlayer manager = pl.khamNgoc.get(j);
                msg.writer().writeInt(manager.idNro);
                msg.writer().writeInt(manager.levelNro);
            }
            pl.sendMessage(msg);
            msg.cleanup();
        } catch (IOException e) {
        } finally {
            if (msg != null) {
                msg.cleanup();
            }
        }
    }

    public void activeKhamNgoc(Player pl, byte active) {
        pl.active_kham_ngoc = active;
        Service.gI().point(pl);
        Send_KhamNgoc_Player(pl);
    }

    /**
     * [port hashirama] Gửi tín hiệu mở UI Khảm Ngọc cho client (sub = 2).
     * Client nhận msg 108 với byte đầu = 2 sẽ mở màn hình Khảm Ngọc
     * với dữ liệu template/player đã được push ngay trước đó.
     */
    public void Send_KhamNgoc_Open(Player pl) {
        Message msg = null;
        try {
            msg = new Message(108);
            msg.writer().writeByte(2);
            pl.sendMessage(msg);
            msg.cleanup();
        } catch (IOException e) {
        } finally {
            if (msg != null) {
                msg.cleanup();
            }
        }
    }

    /**
     * [port hashirama] Thực thi nâng cấp sau khi người chơi xác nhận
     * menu Con Meo ConstNpc.NANG_CAP_KHAM_NGOC (1013).
     */
    public void confirmNangCap(Player pl) {
        byte nro = pl.nroKhamNgoc;
        if (nro < 0 || nro >= KHAM_NGOC.size() || nro >= pl.khamNgoc.size()) {
            return;
        }
        KhamNgoc khamNgoc = KHAM_NGOC.get(nro);
        KhamNgocPlayer manager = pl.khamNgoc.get(nro);
        int level = manager.levelNro;
        int max_level = khamNgoc.khamNgocTemplates.size();
        if ((level + 1) >= max_level) {
            Service.gI().sendThongBao(pl, "Bạn đã đạt cấp tối đa");
            return;
        }
        if (nro > 0) {
            int levelBefore = pl.khamNgoc.get(nro - 1).levelNro;
            int levelBeforeMax = KHAM_NGOC.get(nro - 1).khamNgocTemplates.size();
            if (levelBefore == -1) {
                Service.gI().sendThongBao(pl, "Vui lòng kích hoạt Ngọc rồng " + nro + " sao trước");
                return;
            }
            if (levelBefore < (levelBeforeMax - 1)) {
                Service.gI().sendThongBao(pl, "Vui lòng Nâng Ngọc rồng " + nro + " sao đến cấp tối đa trước");
                return;
            }
        }
        int idTemp = khamNgoc.khamNgocTemplates.get(level + 1).tempId;
        int max_quatity = khamNgoc.khamNgocTemplates.get(level + 1).max_value;
        Item item = InventoryService.gI().findItemBag(pl, idTemp);
        if (item == null || item.quantity < max_quatity) {
            Item it = ItemService.gI().createNewItem((short) idTemp);
            Service.gI().sendThongBao(pl, "Không đủ nguyên liệu. Còn thiếu "
                    + (item == null ? max_quatity : (max_quatity - item.quantity)) + " " + it.template.name);
            return;
        }
        InventoryService.gI().subQuantityItemsBag(pl, item, max_quatity);
        InventoryService.gI().sendItemBag(pl);
        manager.levelNro++;
        Service.gI().point(pl);
        jbcd.dao.PlayerDAO.updateKhamNgoc(pl);
        Send_KhamNgoc_Player(pl);
        Service.gI().sendThongBao(pl, "Nâng cấp Ngọc rồng " + (nro + 1) + " sao lên cấp "
                + (manager.levelNro + 1) + " thành công");
    }

    public void NangCapKhamNgoc(Player pl, byte nro) {
        pl.nroKhamNgoc = nro;
        KhamNgoc khamNgoc = KHAM_NGOC.get(nro);
        KhamNgocPlayer manager = pl.khamNgoc.get(nro);
        int level = manager.levelNro;
        int max_level = khamNgoc.khamNgocTemplates.size();
        if ((level + 1) >= max_level) {
            Service.gI().sendThongBao(pl, "Bạn đã đạt cấp tối đa");
            return;
        }
        int idTemp = khamNgoc.khamNgocTemplates.get(level + 1).tempId;
        int max_quatity = khamNgoc.khamNgocTemplates.get(level + 1).max_value;
        pl.idTempNangCap = idTemp;
        pl.slItem = max_quatity;
        if (nro > 0) {
            int levelBefore = pl.khamNgoc.get(nro - 1).levelNro;
            int levelBeforeMax = KHAM_NGOC.get(nro - 1).khamNgocTemplates.size();
            if (levelBefore == -1) {
                Service.gI().sendThongBao(pl, "Vui lòng kích hoạt Ngọc rồng " + nro + " sao trước");
                return;
            }
            if (levelBefore < (levelBeforeMax - 1)) {
                Service.gI().sendThongBao(pl, "Vui lòng Nâng Ngọc rồng " + nro + " sao đến cấp tối đa trước");
                return;
            }
        }
        Item item = InventoryService.gI().findItemBag(pl, idTemp);
        Item it = ItemService.gI().createNewItem((short) idTemp);
        if (item == null || item.quantity < max_quatity) {
            Service.gI().sendThongBao(pl, "Không đủ nguyên liệu. Còn thiếu " + (item == null ? max_quatity : (max_quatity - item.quantity)) + " " + it.template.name);
            return;
        }
        NpcService.gI().createMenuConMeo(pl, ConstNpc.NANG_CAP_KHAM_NGOC, 0,
                "Bạn có muốn dùng\n"
                + "|1|" + max_quatity + " " + it.template.name
                + "\n|6|để nâng cấp Ngọc rồng " + (nro + 1) + " sao không?\n",
                "Đồng ý", "Đóng");
    }

}
