package nro.minigame;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.concurrent.TimeUnit;

import nro.player.Player;
import nro.server.ServerManager;
import nro.services.Service;
import Utils.Util;

/**
 * Xóc Đĩa (kèm cửa Chẵn/Lẻ) - chuyển từ SuperVIP-SCR-ByBen.
 * 6 cửa: Chẵn x1.8, Lẻ x1.8, 3 Đỏ 1 Trắng x3, 3 Trắng 1 Đỏ x3, Tứ Tử Đỏ x10, Tứ Tử Trắng x10.
 * Luật: 3Đ1T & 3T1Đ là chẵn -> cửa Chẵn cũng ăn kèm. Tứ tử chỉ cửa tứ tử ăn.
 * Cược bằng Hồng Ngọc. Ván 60 giây. Có ép kết quả (admin) + bảo trì hoàn tiền.
 */
public class XocDia implements Runnable {

    public static boolean baoTri = false;

    public static final byte TU_TU_DO = 0;
    public static final byte TU_TU_TRANG = 1;
    public static final byte BA_TRANG_MOT_DO = 2;
    public static final byte BA_DO_MOT_TRANG = 3;
    public static final byte CHAN = 4;
    public static final byte LE = 5;

    private static final long TIME_END = 60000;

    // Tổng cược từng cửa
    public long totalChan, totalLe, total3Trang1Do, total3Do1Trang, totalTuTuTrang, totalTuTuDo;

    // Cược của từng người theo cửa
    private final Map<Player, Long> betChan = new HashMap<>();
    private final Map<Player, Long> betLe = new HashMap<>();
    private final Map<Player, Long> bet3Do1Trang = new HashMap<>();
    private final Map<Player, Long> bet3Trang1Do = new HashMap<>();
    private final Map<Player, Long> betTuTuDo = new HashMap<>();
    private final Map<Player, Long> betTuTuTrang = new HashMap<>();

    private boolean adminChangeMode;
    private boolean ketquaChan, ketquaLe, ketqua3Trang1Do, ketqua3Do1Trang, ketquaTuTuTrang, ketquaTuTuDo;

    public long lastTimeEnd = System.currentTimeMillis();
    public String ketQuaXocDia = "";
    public final List<String> listKetQua = new ArrayList<>();

    private int a, b, c, d, soDo, soTrang;

    private static XocDia instance;

    public static XocDia gI() {
        if (instance == null) {
            instance = new XocDia();
        }
        return instance;
    }

    // ===== TRUY VẤN CƯỢC CÁ NHÂN =====
    public long getBet(Player pl, byte door) {
        if (pl == null) return 0;
        switch (door) {
            case CHAN: return betChan.getOrDefault(pl, 0L);
            case LE: return betLe.getOrDefault(pl, 0L);
            case BA_DO_MOT_TRANG: return bet3Do1Trang.getOrDefault(pl, 0L);
            case BA_TRANG_MOT_DO: return bet3Trang1Do.getOrDefault(pl, 0L);
            case TU_TU_DO: return betTuTuDo.getOrDefault(pl, 0L);
            case TU_TU_TRANG: return betTuTuTrang.getOrDefault(pl, 0L);
            default: return 0;
        }
    }

    public long getTotal(byte door) {
        switch (door) {
            case CHAN: return totalChan;
            case LE: return totalLe;
            case BA_DO_MOT_TRANG: return total3Do1Trang;
            case BA_TRANG_MOT_DO: return total3Trang1Do;
            case TU_TU_DO: return totalTuTuDo;
            case TU_TU_TRANG: return totalTuTuTrang;
            default: return 0;
        }
    }

    public boolean isAllowBetting() {
        long remain = lastTimeEnd - System.currentTimeMillis();
        return remain > 10000 && remain <= TIME_END && !baoTri;
    }

    public StringBuffer getKetQua() {
        StringBuffer kq = new StringBuffer();
        for (String i : listKetQua) {
            kq.append("\n|5|").append(i);
        }
        return kq;
    }

    private void addKetQua() {
        if (listKetQua.size() > 5) {
            listKetQua.clear();
        }
        listKetQua.add(ketQuaXocDia);
    }

    // ===== ĐẶT CƯỢC =====
    public void datCuoc(Player pl, byte door, int tienCuoc) {
        if (pl == null) return;
        if (baoTri) {
            Service.gI().sendThongBao(pl, "Hệ thống đang bảo trì");
            return;
        }
        if (!isAllowBetting()) {
            Service.gI().sendThongBao(pl, "Hết thời gian đặt cược, vui lòng chờ ván sau");
            return;
        }
        if (tienCuoc < 1000) {
            Service.gI().sendThongBao(pl, "Cược tối thiểu 1.000 Hồng ngọc");
            return;
        }
        if (getBet(pl, door) + tienCuoc > 100_000_000L) {
            Service.gI().sendThongBao(pl, "Tối đa 100.000.000 Hồng ngọc mỗi cửa");
            return;
        }
        if (pl.inventory.ruby < tienCuoc) {
            Service.gI().sendThongBao(pl, "Không đủ Hồng ngọc");
            return;
        }

        pl.inventory.ruby -= tienCuoc;
        Service.gI().sendMoney(pl);

        switch (door) {
            case CHAN:
                betChan.put(pl, getBet(pl, CHAN) + tienCuoc);
                totalChan += tienCuoc;
                Service.gI().sendThongBao(pl, "Đặt CHẴN: " + Util.format(tienCuoc) + " Hồng ngọc");
                break;
            case LE:
                betLe.put(pl, getBet(pl, LE) + tienCuoc);
                totalLe += tienCuoc;
                Service.gI().sendThongBao(pl, "Đặt LẺ: " + Util.format(tienCuoc) + " Hồng ngọc");
                break;
            case BA_DO_MOT_TRANG:
                bet3Do1Trang.put(pl, getBet(pl, BA_DO_MOT_TRANG) + tienCuoc);
                total3Do1Trang += tienCuoc;
                Service.gI().sendThongBao(pl, "Đặt 3 Đỏ 1 Trắng: " + Util.format(tienCuoc) + " Hồng ngọc");
                break;
            case BA_TRANG_MOT_DO:
                bet3Trang1Do.put(pl, getBet(pl, BA_TRANG_MOT_DO) + tienCuoc);
                total3Trang1Do += tienCuoc;
                Service.gI().sendThongBao(pl, "Đặt 3 Trắng 1 Đỏ: " + Util.format(tienCuoc) + " Hồng ngọc");
                break;
            case TU_TU_DO:
                betTuTuDo.put(pl, getBet(pl, TU_TU_DO) + tienCuoc);
                totalTuTuDo += tienCuoc;
                Service.gI().sendThongBao(pl, "Đặt Tứ Tử Đỏ: " + Util.format(tienCuoc) + " Hồng ngọc");
                break;
            case TU_TU_TRANG:
                betTuTuTrang.put(pl, getBet(pl, TU_TU_TRANG) + tienCuoc);
                totalTuTuTrang += tienCuoc;
                Service.gI().sendThongBao(pl, "Đặt Tứ Tử Trắng: " + Util.format(tienCuoc) + " Hồng ngọc");
                break;
            default:
                pl.inventory.ruby += tienCuoc;
                Service.gI().sendMoney(pl);
                break;
        }
    }

    // ===== ÉP KẾT QUẢ (ADMIN) =====
    public void changeKetQua(int typeset) {
        switch (typeset) {
            case TU_TU_DO:
            case CHAN:
                a = b = c = d = 1;
                break;
            case TU_TU_TRANG:
            case LE:
                a = b = c = d = 0;
                break;
            case BA_TRANG_MOT_DO:
                a = b = c = d = 0;
                switch (Util.nextInt(0, 3)) {
                    case 0: a = 1; break;
                    case 1: b = 1; break;
                    case 2: c = 1; break;
                    default: d = 1; break;
                }
                break;
            case BA_DO_MOT_TRANG:
                a = b = c = d = 1;
                switch (Util.nextInt(0, 3)) {
                    case 0: a = 0; break;
                    case 1: b = 0; break;
                    case 2: c = 0; break;
                    default: d = 0; break;
                }
                break;
        }
        adminChangeMode = true;
        soDo = a + b + c + d;
        soTrang = 4 - soDo;
        buildKetQuaText();
        applyFlags();
    }

    public String getKetQuaAdminChange() {
        if (!adminChangeMode) {
            return "";
        }
        return "\n|-1|ĐÃ CAN THIỆP KẾT QUẢ\n|7|" + ketQuaXocDia;
    }

    private void buildKetQuaText() {
        ketQuaXocDia = (a == 0 ? "Trắng" : "Đỏ") + " : " + (b == 0 ? "Trắng" : "Đỏ") + " : "
                + (c == 0 ? "Trắng" : "Đỏ") + " : " + (d == 0 ? "Trắng" : "Đỏ") + " ("
                + resultLabel() + ")";
    }

    private String resultLabel() {
        if (soTrang == 4) return "Tứ Tử Trắng";
        if (soDo == 4) return "Tứ Tử Đỏ";
        if (soTrang == 3 && soDo == 1) return "3 Trắng 1 Đỏ";
        if (soDo == 3 && soTrang == 1) return "3 Đỏ 1 Trắng";
        return soDo % 2 == 0 ? "Chẵn" : "Lẻ";
    }

    private void applyFlags() {
        String label = resultLabel();
        ketquaTuTuTrang = label.equals("Tứ Tử Trắng");
        ketquaTuTuDo = label.equals("Tứ Tử Đỏ");
        ketqua3Trang1Do = label.equals("3 Trắng 1 Đỏ");
        ketqua3Do1Trang = label.equals("3 Đỏ 1 Trắng");
        ketquaChan = label.equals("Chẵn");
        ketquaLe = label.equals("Lẻ");
    }

    private void setKetQua() {
        if (adminChangeMode) {
            adminChangeMode = false;
            return;
        }
        int reds;
        do {
            reds = Util.nextInt(0, 1) + Util.nextInt(0, 1) + Util.nextInt(0, 1) + Util.nextInt(0, 1);
        } while ((reds == 0 || reds == 4) && !Util.isTrue(1, 100)); // tứ tử hiếm: ~1% giữ lại

        int[] dice = new int[4];
        for (int i = 0; i < reds; i++) {
            dice[i] = 1;
        }
        for (int i = 3; i > 0; i--) { // xáo trộn vị trí
            int j = Util.nextInt(0, i);
            int t = dice[i];
            dice[i] = dice[j];
            dice[j] = t;
        }
        a = dice[0];
        b = dice[1];
        c = dice[2];
        d = dice[3];
        soDo = reds;
        soTrang = 4 - reds;
        buildKetQuaText();
        applyFlags();
    }

    // ===== BẢO TRÌ + HOÀN TIỀN =====
    public void baoTriAndRefund() {
        baoTri = !baoTri;
        if (baoTri) {
            refundDoor(betChan);
            refundDoor(betLe);
            refundDoor(bet3Do1Trang);
            refundDoor(bet3Trang1Do);
            refundDoor(betTuTuDo);
            refundDoor(betTuTuTrang);
            clearRoundTotals();
        } else {
            lastTimeEnd = System.currentTimeMillis() + TIME_END;
        }
    }

    private void refundDoor(Map<Player, Long> bets) {
        for (Map.Entry<Player, Long> e : bets.entrySet()) {
            Player pl = e.getKey();
            try {
                if (e.getValue() != null && e.getValue() > 0 && isOnline(pl)) {
                    pl.inventory.ruby += e.getValue();
                    Service.gI().sendMoney(pl);
                }
            } catch (Exception ignored) {}
        }
        bets.clear();
    }

    private boolean isOnline(Player pl) {
        try {
            return nro.server.Client.gI().getPlayerByName(pl.name) != null;
        } catch (Exception e) {
            return false;
        }
    }

    // ===== TRẢ THƯỞNG =====
    private final Set<Player> roundWinners = new HashSet<>();

    private void rewardDoor(Map<Player, Long> bets, long multiplier, String doorLabel) {
        for (Map.Entry<Player, Long> e : bets.entrySet()) {
            Player pl = e.getKey();
            long stake = e.getValue() == null ? 0 : e.getValue();
            try {
                if (stake <= 0 || !isOnline(pl)) continue;
                long reward = stake * multiplier / 10; // multiplier x10: 18 = x1.8
                if (reward <= 0) continue;
                roundWinners.add(pl);
                pl.inventory.ruby += reward;
                Service.gI().sendMoney(pl);
                Service.gI().sendThongBaoFromAdmin(pl,
                        "|8|[ Nhà Cái Xóc Đĩa " + ServerManager.NAME + " ]\n\n|2|" + ketQuaXocDia
                        + "\n|5|Cửa thắng: " + doorLabel + "\n\n|7|Bạn Đã Chiến Thắng\n|1|Số Tiền Nhận Được Là\n|3|"
                        + Util.format(reward) + " Hồng ngọc");
            } catch (Exception ignored) {}
        }
    }

    private void loseDoor(Map<Player, Long> bets, String doorLabel) {
        for (Map.Entry<Player, Long> e : bets.entrySet()) {
            Player pl = e.getKey();
            long stake = e.getValue() == null ? 0 : e.getValue();
            try {
                if (stake <= 0 || !isOnline(pl) || roundWinners.contains(pl)) continue;
                Service.gI().sendThongBaoFromAdmin(pl,
                        "|8|[ Nhà Cái Xóc Đĩa " + ServerManager.NAME + " ]\n\n|2|" + ketQuaXocDia
                        + "\n|5|Bạn đặt: " + doorLabel + "\n\n|7|Chúc may mắn lần sau!");
            } catch (Exception ignored) {}
        }
    }

    private void reward() {
        roundWinners.clear();
        if (ketquaTuTuTrang || ketquaTuTuDo) {
            rewardDoor(ketquaTuTuDo ? betTuTuDo : betTuTuTrang, 100,
                    ketquaTuTuDo ? "Tứ Tử Đỏ (x10)" : "Tứ Tử Trắng (x10)");
            loseAllExcept(ketquaTuTuDo ? TU_TU_DO : TU_TU_TRANG);
            return;
        }
        if (ketqua3Do1Trang) {
            rewardDoor(bet3Do1Trang, 30, "3 Đỏ 1 Trắng (x3)");
            rewardDoor(betChan, 18, "Chẵn (x1.8)");
            loseAllExceptMultiple(BA_DO_MOT_TRANG, CHAN);
            return;
        }
        if (ketqua3Trang1Do) {
            rewardDoor(bet3Trang1Do, 30, "3 Trắng 1 Đỏ (x3)");
            rewardDoor(betChan, 18, "Chẵn (x1.8)");
            loseAllExceptMultiple(BA_TRANG_MOT_DO, CHAN);
            return;
        }
        if (ketquaChan) {
            rewardDoor(betChan, 18, "Chẵn (x1.8)");
            loseAllExcept(CHAN);
            return;
        }
        if (ketquaLe) {
            rewardDoor(betLe, 18, "Lẻ (x1.8)");
            loseAllExcept(LE);
        }
    }

    private void loseAllExcept(byte winner) {
        for (byte d = 0; d <= 5; d++) {
            if (d == winner) continue;
            loseDoor(betsOf(d), doorName(d));
        }
    }

    private void loseAllExceptMultiple(byte w1, byte w2) {
        for (byte d = 0; d <= 5; d++) {
            if (d == w1 || d == w2) continue;
            loseDoor(betsOf(d), doorName(d));
        }
    }

    private String doorName(byte door) {
        switch (door) {
            case CHAN: return "Chẵn";
            case LE: return "Lẻ";
            case BA_DO_MOT_TRANG: return "3 Đỏ 1 Trắng";
            case BA_TRANG_MOT_DO: return "3 Trắng 1 Đỏ";
            case TU_TU_DO: return "Tứ Tử Đỏ";
            case TU_TU_TRANG: return "Tứ Tử Trắng";
            default: return "?";
        }
    }

    private Map<Player, Long> betsOf(byte door) {
        switch (door) {
            case CHAN: return betChan;
            case LE: return betLe;
            case BA_DO_MOT_TRANG: return bet3Do1Trang;
            case BA_TRANG_MOT_DO: return bet3Trang1Do;
            case TU_TU_DO: return betTuTuDo;
            case TU_TU_TRANG: return betTuTuTrang;
            default: return new HashMap<>();
        }
    }

    // ===== RESET =====
    private void clearRoundTotals() {
        totalChan = totalLe = total3Trang1Do = total3Do1Trang = totalTuTuTrang = totalTuTuDo = 0;
    }

    @Override
    public void run() {
        while (ServerManager.isRunning) {
            try {
                TimeUnit.SECONDS.sleep(1);
            } catch (InterruptedException e) {
                break;
            }
            if (Util.canDoWithTime(lastTimeEnd, TIME_END)) {
                if (!baoTri) {
                    try {
                        setKetQua();
                        addKetQua();
                        reward();
                    } catch (Exception e) {
                        e.printStackTrace();
                    } finally {
                        clearRoundTotals();
                        betChan.clear();
                        betLe.clear();
                        bet3Do1Trang.clear();
                        bet3Trang1Do.clear();
                        betTuTuDo.clear();
                        betTuTuTrang.clear();
                        ketquaChan = ketquaLe = ketqua3Trang1Do = ketqua3Do1Trang = ketquaTuTuTrang = ketquaTuTuDo = false;
                        roundWinners.clear();
                    }
                }
                lastTimeEnd = System.currentTimeMillis();
            }
        }
    }
}
