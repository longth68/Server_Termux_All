package nro.services;

import consts.ConstNpc;
import java.io.IOException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.ArrayList;
import java.util.List;
import jbcd.ConnectDB;
import models.Item.Item;
import models.Item.ItemOption;
import models.Item.ItemService;
import network.io.Message;
import nro.inventory.InventoryService;
import nro.player.Player;
import org.json.simple.JSONArray;
import org.json.simple.JSONObject;
import org.json.simple.JSONValue;
import Utils.Logger;

/**
 * @author Hoàng Việt - 0857853150
 */
public class RuongSuuTam {

    private static final byte START = 0;
    private static RuongSuuTam i;
    public static List<RuongSuuTamTemplate> listRuong = new ArrayList<>();
    public static final List<Item> listCaiTrang = new ArrayList<>();
    public static final List<Item> listPhuKien = new ArrayList<>();
    public static final List<Item> listPet = new ArrayList<>();
    public static final List<Item> listLinhThu = new ArrayList<>();
    public static final List<Item> listThuCuoi = new ArrayList<>();

    public static byte size_ruong = 20;//mặc định kích thước rương khi tạo acc
    public static final byte MAX_SIZE = 40;//Giới hạn tối đa khi mở rộng rương

    public static final int ID_TEMP = 457;//id vật phẩm tốn khi mở rộng 1 ô
    public static final int QUATITY = 100;//số lượng item mở rộng 1 ô

    public static RuongSuuTam gI() {
        if (i == null) {
            i = new RuongSuuTam();
        }
        return i;
    }

    /**
     * Seed mặc định cho người chơi mới / dữ liệu rỗng:
     * mỗi rương có size_ruong ô trống.
     */
    public void seedDefaultRuongSuuTam(Player pl) {
        if (pl.ruongSuuTam.RuongCaiTrang.isEmpty()) {
            for (int i = 0; i < size_ruong; i++) {
                pl.ruongSuuTam.RuongCaiTrang.add(ItemService.gI().createItemNull());
                pl.ruongSuuTam.RuongPhuKien.add(ItemService.gI().createItemNull());
                pl.ruongSuuTam.RuongPet.add(ItemService.gI().createItemNull());
                pl.ruongSuuTam.RuongLinhThu.add(ItemService.gI().createItemNull());
                pl.ruongSuuTam.RuongThuCuoi.add(ItemService.gI().createItemNull());
            }
        }
    }

    public void loadRuongSuuTam() {
        try (Connection con = ConnectDB.getConnection();
             PreparedStatement ps = con.prepareStatement("SELECT * FROM `ruong_suu_tam`");
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                RuongSuuTamTemplate ruong = new RuongSuuTamTemplate();
                ruong.id = rs.getInt("id");
                ruong.type = rs.getByte("type");
                ruong.id_item = rs.getInt("id_item");
                ruong.option_id = rs.getInt("option_id");
                ruong.param = rs.getInt("param");
                Item it;
                if (ruong.type == 0) {
                    it = ItemService.gI().createNewItem((short) ruong.id_item, 1);
                    it.itemOptions.add(new ItemOption(ruong.option_id, ruong.param));
                    listCaiTrang.add(it);
                } else if (ruong.type == 1) {
                    it = ItemService.gI().createNewItem((short) ruong.id_item, 1);
                    it.itemOptions.add(new ItemOption(ruong.option_id, ruong.param));
                    listPhuKien.add(it);
                } else if (ruong.type == 2) {
                    it = ItemService.gI().createNewItem((short) ruong.id_item, 1);
                    it.itemOptions.add(new ItemOption(ruong.option_id, ruong.param));
                    listPet.add(it);
                } else if (ruong.type == 3) {
                    it = ItemService.gI().createNewItem((short) ruong.id_item, 1);
                    it.itemOptions.add(new ItemOption(ruong.option_id, ruong.param));
                    listLinhThu.add(it);
                } else if (ruong.type == 4) {
                    it = ItemService.gI().createNewItem((short) ruong.id_item, 1);
                    it.itemOptions.add(new ItemOption(ruong.option_id, ruong.param));
                    listThuCuoi.add(it);
                }
                listRuong.add(ruong);
            }
            Logger.success("Load Ruong Suu Tam thanh cong (" + listRuong.size() + ")");
        } catch (Exception ex) {
            Logger.logException(RuongSuuTam.class, ex);
        }
    }

    public void Send_RuongSuuTamTemplate(Player pl) {
        Message msg = null;
        try {
            msg = new Message(109);
            msg.writer().writeByte(0);
            msg.writer().writeByte(START);
            msg.writer().writeByte(pl.active_ruong_suu_tam);
            msg.writer().writeInt(listRuong.size());

            msg.writer().writeInt(listCaiTrang.size());
            msg.writer().writeInt(listPhuKien.size());
            msg.writer().writeInt(listPet.size());
            msg.writer().writeInt(listLinhThu.size());
            msg.writer().writeInt(listThuCuoi.size());

            for (int j = 0; j < listRuong.size(); j++) {
                RuongSuuTamTemplate ruong = listRuong.get(j);
                msg.writer().writeInt(ruong.id);
                msg.writer().writeByte(ruong.type);
                msg.writer().writeShort(ruong.id_item);
                msg.writer().writeByte(ruong.option_id);
                msg.writer().writeInt(ruong.param);
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

    public void SendAllRuong(Player player) {
        Send_RuongCaiTrang(player);
        Send_RuongPhuKien(player);
        Send_RuongPet(player);
        Send_RuongLinhThu(player);
        Send_RuongThuCuoi(player);
    }

    public void Send_RuongCaiTrang(Player pl) {
        InventoryService.gI().sortItems(pl.ruongSuuTam.RuongCaiTrang);
        sendRuong(pl, (byte) 1, pl.ruongSuuTam.RuongCaiTrang);
    }

    public void Send_RuongPhuKien(Player pl) {
        InventoryService.gI().sortItems(pl.ruongSuuTam.RuongPhuKien);
        sendRuong(pl, (byte) 2, pl.ruongSuuTam.RuongPhuKien);
    }

    public void Send_RuongPet(Player pl) {
        InventoryService.gI().sortItems(pl.ruongSuuTam.RuongPet);
        sendRuong(pl, (byte) 3, pl.ruongSuuTam.RuongPet);
    }

    public void Send_RuongLinhThu(Player pl) {
        InventoryService.gI().sortItems(pl.ruongSuuTam.RuongLinhThu);
        sendRuong(pl, (byte) 4, pl.ruongSuuTam.RuongLinhThu);
    }

    public void Send_RuongThuCuoi(Player pl) {
        InventoryService.gI().sortItems(pl.ruongSuuTam.RuongThuCuoi);
        sendRuong(pl, (byte) 5, pl.ruongSuuTam.RuongThuCuoi);
    }

    /**
     * Layout message 109 (sub 1..5) - giữ nguyên byte layout nguồn:
     * writeByte(sub), writeByte(size), sau đó mỗi item không null:
     * writeShort(tempId), writeInt(quantity), writeUTF(info), writeUTF(content),
     * writeByte(so option), [writeByte(optionId), writeInt(param)]...
     */
    private void sendRuong(Player pl, byte sub, List<Item> items) {
        Message msg = null;
        try {
            msg = new Message(109);
            msg.writer().writeByte(sub);
            msg.writer().writeByte(items.size());
            for (int i = 0; i < items.size(); i++) {
                Item item = items.get(i);
                if (item == null || !item.isNotNullItem()) {
                    continue;
                }
                msg.writer().writeShort(item.template.id);
                msg.writer().writeInt(item.quantity);
                msg.writer().writeUTF(item.getInfo());
                msg.writer().writeUTF(item.getContent());
                List<ItemOption> itemOptions = item.itemOptions;
                msg.writer().writeByte(itemOptions.size()); //options
                for (ItemOption o : itemOptions) {
                    msg.writer().writeByte(o.optionTemplate.id);
                    msg.writer().writeInt(o.param);
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

    public void mangItem(Player pl, byte type, int idTemp) {
        List<Item> listItem = getRuongList(pl, type);
        Item item = InventoryService.gI().findItemBag(pl, idTemp);
        if (item == null) {
            Service.gI().sendThongBao(pl, "Không thể thực hiện");
            return;
        }
        Item itemCheck = InventoryService.gI().findItem(listItem, idTemp);
        if (itemCheck != null) {
            Service.gI().sendThongBao(pl, "Đã có vật phẩm này trong Rương Sưu Tầm rồi!!!!");
            return;
        }
        int sizeNull = 0;
        for (int i = 0; i < listItem.size(); i++) {
            Item it = listItem.get(i);
            if (!it.isNotNullItem()) {
                sizeNull++;
            }
        }
        if (sizeNull == 0) {
            Service.gI().sendThongBao(pl, "Rương đã đầy rồi!!!!");
            return;
        }
        Item copy = ItemService.gI().copyItem(item);
        copy.quantity = 1;
        InventoryService.gI().addItemList(listItem, copy);
        InventoryService.gI().subQuantityItemsBag(pl, item, 1);
        InventoryService.gI().sendItemBag(pl);
        Service.gI().point(pl);
        SendAllRuong(pl);
    }

    public void thaoItem(Player pl, byte type, int idTemp) {
        List<Item> listItem = getRuongList(pl, type);
        Item item = InventoryService.gI().findItem(listItem, idTemp);
        if (item == null) {
            Service.gI().sendThongBao(pl, "Không thể thực hiện");
            return;
        }
        Item copy = ItemService.gI().copyItem(item);
        copy.quantity = 1;
        InventoryService.gI().addItemBag(pl, copy);
        InventoryService.gI().subQuantityItem(listItem, item, 1);
        InventoryService.gI().sendItemBag(pl);
        Service.gI().point(pl);
        SendAllRuong(pl);
    }

    private List<Item> getRuongList(Player pl, byte type) {
        if (type == 0) {
            return pl.ruongSuuTam.RuongCaiTrang;
        } else if (type == 1) {
            return pl.ruongSuuTam.RuongPhuKien;
        } else if (type == 2) {
            return pl.ruongSuuTam.RuongPet;
        } else if (type == 3) {
            return pl.ruongSuuTam.RuongLinhThu;
        } else if (type == 4) {
            return pl.ruongSuuTam.RuongThuCuoi;
        }
        return new ArrayList<>();
    }

    public void moRongRuong(Player pl, byte type) {
        pl.typeMoRuong = type;
        Item it = ItemService.gI().createNewItem((short) ID_TEMP);
        NpcService.gI().createMenuConMeo(pl, ConstNpc.MO_RONG_RUONG_SUU_TAM, 0,
                "Bạn có muốn dùng\n"
                + "|1|" + QUATITY + " " + it.template.name
                + "\n|6|để mở thêm 1 ô " + nameRuong(pl, type) + " không?\n"
                + "|7|Tối đa " + MAX_SIZE + " Ô",
                "Đồng ý", "Đóng");
    }

    public String nameRuong(Player pl, byte type) {
        if (type == 0) {
            return "Rương Cải Trang";
        } else if (type == 1) {
            return "Rương Phụ Kiện";
        } else if (type == 2) {
            return "Rương Pet";
        } else if (type == 3) {
            return "Rương Linh Thú";
        } else if (type == 4) {
            return "Rương Thú Cưỡi";
        }
        return "";
    }

    public void activeRuongSuuTam(Player pl, byte active) {
        pl.active_ruong_suu_tam = active;
        Service.gI().point(pl);
        Send_RuongSuuTamTemplate(pl);
    }

}
