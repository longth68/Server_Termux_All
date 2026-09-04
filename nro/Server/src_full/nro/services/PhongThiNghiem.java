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
public class PhongThiNghiem {

    public static final byte SIZE = 3;//số lọ được tạo khi vừa tạo acc
    public static final byte MAX_SIZE = 26;//số lượng tối đa khi mở thêm lọ thí nghiệm

    public static final int ID_ITEM_MO_RONG = 457;//ID Item tốn khi mở rộng
    public static final int SO_LUONG = 100;//Số lượng tốn khi mở rộng

    public static final int ID_ITEM_TANG_TOC = 457;//ID Item tốn khi tăng tốc
    public static final int SO_LUONG_TANG_TOC = 22;//Số lượng tốn khi tăng tốc
    public static final long TIME_TANG_TOC = 600000;//10phut la 60 * 10 * 1000 = 600000 miliGiay

    private static final byte START = 0;//0 là tắt chức năng, 1 là mở

    public int id;
    public String name_tab;
    public String name_binh;
    public final List<PhongThiNghiem_Template> items = new ArrayList<>();
    public int thoi_gian;
    public int idItem_Nhan;
    public String info;
    public byte color; //màu: 0(Xanh dương) || 1(Đỏ) || 2(Cam) || 3(Xanh lá) || 4(Tím) || 5(Vàng) || 6(Hồng) || 7(Xanh nhạt) || 8(Nâu) || 9(Đen)

    public static final List<PhongThiNghiem> PHONG_THI_NGHIEM = new ArrayList<>();
    private static PhongThiNghiem i;

    public static PhongThiNghiem gI() {
        if (i == null) {
            i = new PhongThiNghiem();
        }
        return i;
    }

    /**
     * Seed mặc định cho người chơi mới / dữ liệu rỗng: SIZE lọ trống.
     */
    public void seedDefaultPhongThiNghiem(Player pl) {
        if (pl.phongThiNghiem.isEmpty()) {
            for (int i = 0; i < SIZE; i++) {
                PhongThiNghiem_Player ptn = new PhongThiNghiem_Player();
                ptn.idBinh = -1;
                ptn.timeCheTao = 0;
                pl.phongThiNghiem.add(ptn);
            }
        }
    }

    public long thoiGianDieuChe() {
        return this.thoi_gian * 1000 * 60;
    }

    private static String msToTime(long ms) {
        ms = ms - System.currentTimeMillis();
        if (ms < 0) {
            ms = 0;
        }
        long giay = 0;
        long phut = 0;
        long gio = 0;
        long ngay = 0;
        giay = ms / 1000;
        phut = giay / 60;
        giay = giay % 60;
        gio = phut / 60;
        phut = phut % 60;
        ngay = gio / 24;
        gio = gio % 24;
        String giayString = String.valueOf(giay);
        String phutString = String.valueOf(phut);
        String gioString = String.valueOf(gio);
        String ngayString = String.valueOf(ngay);
        String time;
        if (ngay != 0) {
            time = ngayString + " Ngày, " + gioString + " giờ, " + phutString + "phút, " + giayString + "giây";
        } else if (gio != 0) {
            time = gioString + " giờ, " + phutString + "phút, " + giayString + "giây";
        } else if (phut != 0) {
            time = phutString + "phút, " + giayString + "giây";
        } else if (giay != 0) {
            time = giayString + "giây";
        } else {
            time = "Hết hạn";
        }
        return time;
    }

    public void loadPhongThiNghiem() {
        try (Connection con = ConnectDB.getConnection();
             PreparedStatement ps = con.prepareStatement("SELECT * FROM `phong_thi_nghiem`");
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                PhongThiNghiem ptn = new PhongThiNghiem();
                ptn.id = rs.getInt("id");
                ptn.name_tab = rs.getString("name_tab");
                ptn.name_binh = rs.getString("name_binh");
                ptn.thoi_gian = rs.getInt("thoi_gian");
                ptn.idItem_Nhan = rs.getInt("item_nhan");
                ptn.info = rs.getString("info");
                ptn.color = rs.getByte("color");
                JSONArray jArr = (JSONArray) JSONValue.parse(rs.getString("items"));
                if (jArr == null) {
                    continue;
                }
                for (int i = 0; i < jArr.size(); i++) {
                    JSONObject obj = (JSONObject) jArr.get(i);
                    PhongThiNghiem_Template itemThuoc = new PhongThiNghiem_Template();
                    itemThuoc.tempId = Integer.parseInt(String.valueOf(obj.get("tempid")));
                    itemThuoc.quantity = Integer.parseInt(String.valueOf(obj.get("quantity")));
                    ptn.items.add(itemThuoc);
                }
                PHONG_THI_NGHIEM.add(ptn);
            }
            Logger.success("Load Phong Thi Nghiem thanh cong (" + PHONG_THI_NGHIEM.size() + ")");
        } catch (Exception ex) {
            Logger.logException(PhongThiNghiem.class, ex);
        }
    }

    public void Send_PhongThiNghiem_Template(Player pl) {
        Message msg = null;
        try {
            msg = new Message(110);
            msg.writer().writeByte(0);
            msg.writer().writeByte(START);
            msg.writer().writeByte(MAX_SIZE);
            msg.writer().writeByte(PHONG_THI_NGHIEM.size());

            for (int j = 0; j < PHONG_THI_NGHIEM.size(); j++) {
                PhongThiNghiem manager = PHONG_THI_NGHIEM.get(j);
                msg.writer().writeInt(manager.id);
                msg.writer().writeUTF(manager.name_tab);
                msg.writer().writeUTF(manager.name_binh);
                msg.writer().writeUTF(msToTime(manager.thoiGianDieuChe()));
                msg.writer().writeInt(manager.idItem_Nhan);
                msg.writer().writeUTF(manager.info);
                msg.writer().writeByte(manager.color);
                msg.writer().writeByte(manager.items.size());
                for (int k = 0; k < manager.items.size(); k++) {
                    PhongThiNghiem_Template template = manager.items.get(k);
                    msg.writer().writeInt(template.tempId);
                    msg.writer().writeInt(template.quantity);
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

    public void Send_PhongThiNghiem_Player(Player pl) {
        Message msg = null;
        try {
            msg = new Message(110);
            msg.writer().writeByte(1);
            msg.writer().writeByte(pl.phongThiNghiem.size());

            for (int j = 0; j < pl.phongThiNghiem.size(); j++) {
                PhongThiNghiem_Player manager = pl.phongThiNghiem.get(j);
                msg.writer().writeInt(manager.idBinh);
                msg.writer().writeLong(manager.timeCheTao - System.currentTimeMillis());
                if (manager.idBinh != -1 && manager.timeCheTao > 0 && manager.timeCheTao - System.currentTimeMillis() > 0) {
                    msg.writer().writeUTF(msToTime(manager.timeCheTao - System.currentTimeMillis()));
                } else if (manager.idBinh != -1 && manager.timeCheTao - System.currentTimeMillis() <= 0) {
                    msg.writer().writeUTF("Chế tạo xong");
                } else {
                    msg.writer().writeUTF("");
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

    public void dieu_che(Player pl, int vitri, int type) {
        PhongThiNghiem ptn = PHONG_THI_NGHIEM.get(type);
        for (int i = 0; i < ptn.items.size(); i++) {
            Item item = InventoryService.gI().findItemBag(pl, ptn.items.get(i).tempId);
            if (item == null || item.quantity < ptn.items.get(i).quantity) {
                Service.gI().sendThongBao(pl, "Không đủ nguyên liệu");
                return;
            }
        }
        String text = "";
        for (int i = 0; i < ptn.items.size(); i++) {
            Item it = ItemService.gI().createNewItem((short) ptn.items.get(i).tempId);
            it.quantity = ptn.items.get(i).quantity;
            text += "|5|-" + it.quantity + " " + it.template.name + (i == (ptn.items.size() - 1) ? "" : "\n");
        }
        pl.vitriBinhDieuChe = vitri;
        pl.typeBinhDieuChe = type;
        NpcService.gI().createMenuConMeo(pl, ConstNpc.DIEU_CHE, 0,
                "Bạn có muốn dùng\n"
                + text
                + "\n|6|Để điều chế " + ptn.name_binh + " không?"
                + "\n|7|Thời gian điều chế: " + msToTime(ptn.thoiGianDieuChe()),
                "Đồng ý", "Đóng");
    }

    public void nhan_item(Player pl, int id, int vitri) {
        PhongThiNghiem ptn = PHONG_THI_NGHIEM.get(id);
        if (pl.phongThiNghiem.get(vitri).timeCheTao - System.currentTimeMillis() > 0) {
            Service.gI().sendThongBao(pl, "Chưa xong mà");
            return;
        }
        if (pl.phongThiNghiem.get(vitri).idBinh == -1 || pl.phongThiNghiem.get(vitri).timeCheTao == 0) {
            Service.gI().sendThongBao(pl, "Không thể thực hiện");
            return;
        }
        if (InventoryService.gI().getCountEmptyBag(pl) > 0) {
            pl.phongThiNghiem.get(vitri).idBinh = -1;
            pl.phongThiNghiem.get(vitri).timeCheTao = 0;
            Item it = ItemService.gI().createNewItem((short) ptn.idItem_Nhan);
            InventoryService.gI().addItemBag(pl, it);
            InventoryService.gI().sendItemBag(pl);
            Service.gI().sendThongBao(pl, "Nhận thành công " + it.template.name);
            Send_PhongThiNghiem_Player(pl);
        } else {
            Service.gI().sendThongBao(pl, "Hành trang không đủ chổ trống");
        }
    }

    public void tangTocPtn(Player pl, int id, int vitri) {
        PhongThiNghiem_Player ptnPL = pl.phongThiNghiem.get(vitri);
        PhongThiNghiem ptn = PHONG_THI_NGHIEM.get(id);
        if (ptnPL.timeCheTao - System.currentTimeMillis() <= 0) {
            Service.gI().sendThongBao(pl, "Đã chế tạo xong. Không thể Tăng tốc");
            return;
        }
        pl.vitriBinhDieuChe = vitri;
        pl.typeBinhDieuChe = id;
        Item it = ItemService.gI().createNewItem((short) ID_ITEM_TANG_TOC);
        NpcService.gI().createMenuConMeo(pl, ConstNpc.TANG_TOC, 0,
                "Bạn có muốn dùng"
                + "\n|1|" + SO_LUONG_TANG_TOC + " " + it.template.name
                + "\n|6|để Tăng tốc " + msToTime(TIME_TANG_TOC) + " " + ptn.name_binh + " không?",
                "Đồng ý", "Đóng");
    }

    public void huyPtn(Player pl, int id, int vitri) {
        PhongThiNghiem_Player ptnPL = pl.phongThiNghiem.get(vitri);
        PhongThiNghiem ptn = PHONG_THI_NGHIEM.get(id);
        if (ptnPL.timeCheTao - System.currentTimeMillis() <= 0) {
            Service.gI().sendThongBao(pl, "Đã chế tạo xong. Không thể Hủy bỏ");
            return;
        }
        if (InventoryService.gI().getCountEmptyBag(pl) < ptn.items.size()) {
            Service.gI().sendThongBao(pl, "Hành trang không đủ chổ trống");
            return;
        }
        pl.vitriBinhDieuChe = vitri;
        pl.typeBinhDieuChe = id;
        String text = "";
        for (int i = 0; i < ptn.items.size(); i++) {
            Item it = ItemService.gI().createNewItem((short) ptn.items.get(i).tempId);
            it.quantity = ptn.items.get(i).quantity;
            text += "|5|-" + it.quantity + " " + it.template.name + (i == (ptn.items.size() - 1) ? "" : "\n");
        }
        NpcService.gI().createMenuConMeo(pl, ConstNpc.HUY_PTN, 0,
                "Bạn có muốn hủy Điều chế"
                + "\n|1|" + ptn.name_binh + " không?"
                + "\n|7|Hoàn trả toàn bộ nguyên liệu:"
                + "\n" + text,
                "Đồng ý", "Đóng");
    }

    public void mo_rong(Player pl) {
        Item it = ItemService.gI().createNewItem((short) ID_ITEM_MO_RONG);
        NpcService.gI().createMenuConMeo(pl, ConstNpc.MO_RONG_PHONG_THI_NGHIEM, 0,
                "Bạn có muốn dùng\n"
                + "|1|" + SO_LUONG + " " + it.template.name
                + "\n|6|để mở thêm 1 chổ trống không?\n"
                + "|2|Đang có: " + pl.phongThiNghiem.size() + " lọ\n"
                + "|7|Tối đa " + MAX_SIZE + " lọ",
                "Đồng ý", "Đóng");
    }
}
