package event;

/*
 * @Author: Anwin
 * @Dev: Anwin
 */

import event.BlackFriday.BlackFriday;
import nro.services.Service;
import event.Christmas.Christmas;
import event.Halloween.Halloween;
import event.HungKingsEvent.HungVuong;
import event.List.Nomal;
import event.List.InternationalWomensDay.InternationalWomensDay;
import event.List.TopUp;
import event.List.WomensDay.WomensDay;
import event.LunarNewYear.LunarNewYear;
import event.MidAutumnFestival.TrungThu;
import event.ValentineDay.ValentineDay;
import event.VuLanFestival.VuLanFestival;
import nro.player.Player;
import nro.server.Client;

public class EventManager {

    private static EventManager instance;

    public static boolean NEW_SEVER = false;

    public static boolean LUNNAR_NEW_YEAR = false;

    public static boolean CHRISTMAS = false;

    public static boolean VU_LAN_FESTIVAL = false;

    public static boolean HALLOWEEN = false;

    public static boolean INTERNATIONAL_WOMANS_DAY = false;

    public static boolean TRUNG_THU = false;

    public static boolean HUNG_VUONG = false;

    public static boolean BLACK_FRIDAY = false;

    public static boolean VALENTINE_DAY = false; // Chưa xong

    public static boolean DAY_20_10 = false;

    public static boolean TOP_UP = false;

    public static EventManager gI() {
        if (instance == null) {
            instance = new EventManager();
        }
        return instance;
    }

    public void init() {
        new Nomal().init();

        if (LUNNAR_NEW_YEAR) {
            new LunarNewYear().init();
        }
        if (CHRISTMAS) {
            new Christmas().init();
        }
        if (VU_LAN_FESTIVAL) {
            new VuLanFestival().init();
        }
        if (HALLOWEEN) {
            new Halloween().init();
        }
        if (INTERNATIONAL_WOMANS_DAY) {
            new InternationalWomensDay().init();
        }
        if (HUNG_VUONG) {
            new HungVuong().init();
        }
        if (TRUNG_THU) {
            new TrungThu().init();
        }
        if (BLACK_FRIDAY) {
            new BlackFriday().init();
        }
        if (VALENTINE_DAY) {
            new ValentineDay().init();
        }
        if (DAY_20_10) {
            new WomensDay().init();
        }
        if (TOP_UP) {
            new TopUp().init();
        }
    }

    public void setCurrentEvent(int eventId) {
        LUNNAR_NEW_YEAR = false;
        CHRISTMAS = false;
        VU_LAN_FESTIVAL = false;
        HALLOWEEN = false;
        INTERNATIONAL_WOMANS_DAY = false;
        TRUNG_THU = false;
        HUNG_VUONG = false;
        BLACK_FRIDAY = false;
        VALENTINE_DAY = false;
        DAY_20_10 = false;
        TOP_UP = false;

        switch (eventId) {
            case 1:  LUNNAR_NEW_YEAR = true; break;
            case 2:  TRUNG_THU = true; break;
            case 3:  HALLOWEEN = true; break;
            case 4:  CHRISTMAS = true; break;
            case 5:  VU_LAN_FESTIVAL = true; break;
            case 6:  INTERNATIONAL_WOMANS_DAY = true; break;
            case 7:  HUNG_VUONG = true; break;
            case 8:  BLACK_FRIDAY = true; break;
            case 9:  VALENTINE_DAY = true; break;
            case 10: DAY_20_10 = true; break;
            case 11: TOP_UP = true; break;
            default: break;
        }
    }

    public void toggleEvent(int eventId, boolean on) {
        switch (eventId) {
            case 1:  LUNNAR_NEW_YEAR = on; break;
            case 2:  TRUNG_THU = on; break;
            case 3:  HALLOWEEN = on; break;
            case 4:  CHRISTMAS = on; break;
            case 5:  VU_LAN_FESTIVAL = on; break;
            case 6:  INTERNATIONAL_WOMANS_DAY = on; break;
            case 7:  HUNG_VUONG = on; break;
            case 8:  BLACK_FRIDAY = on; break;
            case 9:  VALENTINE_DAY = on; break;
            case 10: DAY_20_10 = on; break;
            case 11: TOP_UP = on; break;
            default: break;
        }
    }

    public static boolean isEventActive(int eventId) {
        switch (eventId) {
            case 1:  return LUNNAR_NEW_YEAR;
            case 2:  return TRUNG_THU;
            case 3:  return HALLOWEEN;
            case 4:  return CHRISTMAS;
            case 5:  return VU_LAN_FESTIVAL;
            case 6:  return INTERNATIONAL_WOMANS_DAY;
            case 7:  return HUNG_VUONG;
            case 8:  return BLACK_FRIDAY;
            case 9:  return VALENTINE_DAY;
            case 10: return DAY_20_10;
            case 11: return TOP_UP;
            default: return false;
        }
    }

    public void loadActiveEvents(String activeIds) {
        LUNNAR_NEW_YEAR = false;
        CHRISTMAS = false;
        VU_LAN_FESTIVAL = false;
        HALLOWEEN = false;
        INTERNATIONAL_WOMANS_DAY = false;
        TRUNG_THU = false;
        HUNG_VUONG = false;
        BLACK_FRIDAY = false;
        VALENTINE_DAY = false;
        DAY_20_10 = false;
        TOP_UP = false;
        if (activeIds == null || activeIds.trim().isEmpty() || activeIds.equals("0")) return;
        for (String s : activeIds.split("-")) {
            try {
                int id = Integer.parseInt(s.trim());
                toggleEvent(id, true);
            } catch (Exception ignored) {}
        }
    }
}