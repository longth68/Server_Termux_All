<?php
include __DIR__ . '/Connections.php';
include __DIR__ . '/Sessions.php';
include __DIR__ . '/Configs.php';

if (isset($_FixWeb) && $_FixWeb == 1) {
    echo "Máy chủ đang bảo trì website. Vui lòng chờ nhé!";
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <link rel="icon" href="/favicon.ico" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="theme-color" content="#000000" />
    <meta name="description" content="<?= $_Description ?>" />
    <meta property="og:site_name" content="<?= $_Title ?>" />
    <meta property="og:title" content="<?= $_Title ?>" />
    <meta property="og:image" content="/Assets/Images/og_img.jpg" />
    <meta property="og:url" content="<?= $_Domain ?>" />
    <meta property="og:description" content="<?= $_Description ?>" />
    <link rel="apple-touch-icon" href="/Assets/Images/Logo.png" />
    <title><?= $_Title ?></title>
    <link href="/Assets/Css/Main.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = { corePlugins: { preflight: false } }
    </script>
    <style data-styled="active" data-styled-version="6.1.3"></style>
</head>

<body>
    <div id="root">
        <div class="max-w-3xl mx-auto">
            <img src="/Assets/Images/<?= $_Logo ?>" alt="headerLogo">
            <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAA1IAAAAMCAMAAABflHwvAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAABtQTFRFe9T/1+Hc1uneV8r/Ls//EbnvV677QqL3////y78SjwAAAAl0Uk5T//////////8AU094EgAAAUZJREFUeNrsl+tuwjAMhdP5Et7/iedL2qZTSoBNWn6cAyrUx7G/VHIL5TGQMm2s9XGnmf8b1dTHPvjB/7/8JZvo0VFpo0LEXFj2zjPfJGxRli5PLj2H/nVnjiHMLOboYON3PvjBvxJ/sfRWXz28fW2+QLyzpDH1LU72JhKPWbAtaB2f+EfSWT+qi33l3Pl5bxn74Af/WvwlFkR2TFklYTk6WXzmWw2JkAamH/xD81Snfj15D3lOaau76zH0wQ/+tfhLuhqDm4u60ZR99XM/M/yV9wGrzbFXfsHXjFO7A/B+tfab0MwHP/jX4vc5ZZL8adqeh10zmfknVsz+lglCQXDBHvv52IxD7la0LyszH/zgX4u/qP/P0kuR6/nE751+jKu+4f8oOlh664Mf/GvxlwpB0B8KIwVBGCkIwkhBEEYKgqD39S3AABG6/EvBV5qPAAAAAElFTkSuQmCC"
                alt="bgTopMenu" class="sc-gsDLol eeXmIL">
            <div>
                <div class="flex flex-row justify-evenly items-center">
                    <a aria-current="page"
                        class="circle <?php echo ($_SERVER['REQUEST_URI'] == '/') ? 'active' : ''; ?>" href="/">
                        <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEYAAABGCAMAAABG8BK2AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAABhQTFRF//5yHx4etwAAz8/PAAAA/////wAA////WBMrwwAAAAh0Uk5T/////////wDeg71ZAAACNUlEQVR42qzYi7KEIAgAULSy///jq/gCBNfu4MzWTptnqYQseHctkLbdEbbERdqWAtO4lGZKoCO93zPalA6ZjjxLsyFYkagShIorJJkQY0zp2bSU8i5hy+RQMpJ2Dv4cZUCwhrJ12s8yIOBKGm2r1IB0hik/Ge6AoVSHDj2hcAcsJXGkQslywFDyOVxzIS47hYURSGN6fneGQ5LhwcTK0JxGKkpnhAOmso74MjgNR2Gaoqa+dBizKlZdaQEtzsLYodCANOaLYjiC+a1IZzB5THxRpJPHVmNIHfnIYOJRppW1A2U6rawiE2blRQVYy3vgaiywoTOrc0Bmlmtk6iHXVelbVmWZu+dP3VpyZXbjDBb98pe1SxpfxjJ1jd0+VubCqzf+vn1Sj6Ix+aguwZBT05i77Hv3gxpMg3AtmCsAPTUimn4wLRBIIzDOPAtTE6QfFDk9QANLeH2Pmdavkvk4+5U6ZNgYgdIfN91kOB0w733XRVmP73zTF6b368yLm+5TRp9QYWqMtPo3U86sAwPnDHHIZWEZPxTBXCrTc1kMRY0pyaCk5jgVLaV/MlqhoGeUFA3BbApFLVuEyQNkpiZlgla2eBE9iUYpokZJ350braQbNxhype5Z/mB3g1Fvd+q42dzujJtvzck1262brzUVqKlMsl1TCOM0MfGaJjlN2rymkE4TWq/ptddk3+nRw+tByOuxzOsh0euR1esB2utx3u3lgterDrcXL26vgdxeSn19RfYnwACW613AZHVZ6AAAAABJRU5ErkJggg=="
                            alt="homeNav" />
                    </a>
                    <a class="circle <?php echo ($_SERVER['REQUEST_URI'] == '/Users/Downloads') ? 'active' : ''; ?>"
                        href="/Users/Downloads">
                        <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEYAAABGCAMAAABG8BK2AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAABhQTFRF//5yHx4etwAAz8/PAAAA/////wAA////WBMrwwAAAAh0Uk5T/////////wDeg71ZAAACMUlEQVR42tTY3XrCIAwG4A+K9P7veAgBAknQbjkZB51T+z4pkvCD+9QCa8cv4khcrB0pmMalNFOCjvT7XqNN6UumIy/RbAgSiSrBqCihnQkxxpxfh5Zz+Uo4MiWUguSTUz+Oe0CQoRwd+ngPCKuSRzsqLSCdWZSPzOrAUJrDh96mrA4sJa9Ig7LlwFBKH8pciOJLQTAbQkzP786s0M6swcTG8JyuVNydEQ5MRY749+A0HIUhRU393VkYqVh1hQISjmDsUHhAGvNEMZyN+azszmDKmDgqKO3glLFFDKsjGpOzydTE4wyVNeWRgI0ZDpXVyoRZeRcF1G52BXdmdQ6VmeV6Ydqjo75o18wZdtvK1KLPe7bch/ong/6bj8WmD8lcQfTt+0rhsBp3bQzrmo1J7xsTiMGBuQJ412jRUBflA/MSTLgVhvqF9011njLUP3s43zJ9vCSMQfQL5k6JX+qLPzMpcecJc6oW/4ghp/8sM7lnxtefSxl+MhnQBglLbkrN3AePSAYtNWnkjuSmXKDcgpaaWqHoFaJnZS+7jDkUilG2KP7J9FD6Q6llSxRRDIKCqE/IolGKqFLSwSJBY8AZraQrEwz1xkxulESYD2VNMGK6m2OEkvtO7D19ulMmX0rokZU9UesbxuSrLQVSzWeFKc1aCjgtTLyWSU6LNq8lpNOC1mt57bXYd9p6eG2EvLZlXptEry2r1wbaazvvdrjgddThdvDidgzkdij19IjsR4ABAK8yXydRxRnDAAAAAElFTkSuQmCC"
                            alt="downloadNav" />
                    </a>
                    <a class="circle <?php echo ($_SERVER['REQUEST_URI'] == '/Users/Forum') ? 'active' : ''; ?>"
                        href="/Users/Forum">
                        <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEYAAABGCAMAAABG8BK2AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAABhQTFRF//5yHx4etwAAz8/PAAAA/////wAA////WBMrwwAAAAh0Uk5T/////////wDeg71ZAAACN0lEQVR42qzYi46EIAwF0IoK///Hi1CgT0Y3JdlZzTgnqNyCQtm1g7TtgbAlTtK2FLjGaTRXAhsZv7tnW9JLZiC3aj4EGkkmQaikIckcKaWc703LuR5ybJnalYrkndO+TrJDoLuydfBr2SHgSp5tq/QO2QxTfjLcAUfpDh16QuEOeErmSIey54Cj1Guos5DUQYdiBILMyPdgOCQZ3pnUGZrpRiXpzO6Aq+gR/wxOxzEYVMzoS4cxWvHqCnZIOYrxu0I7ZDFfFMcRzG9FOpOpY2KrQGuuU8cWMqSOWMzzjcO04FEGy5pxSlAdAOu0sKw25liVdyiA5wG8lXluzVnV+WjMKteDaR0G6KeT8fPZJwz5GWda0cerAfi7sQHNzSvJZPrQzLmYSUwmE+YUDLk0jLkI0wV6wzhzHkAvzbY3/bws5lbMUdxr0/cKcV4w+k7V04SPTG+XGDYXycQbplxX/1sbZex9Yy5U6r++M60PjAy4US0+MzKY/+0NLxNvmOnAKlYr7STievidJoNDBzehiIjLMFjRpAO5YLh5xFU0rUJBY9W2RkJJwfGZWbZmAR5W78iKuFm2VBEVIR+hAsaIImqXdB5y6My49VZJtyeYeafW9ZkR9yYYY7obIS8k6iPi9nRnT74s5AWz2badyddeCoyQM+b5cJcCQQuTqGVS0KItagkZtKCNWl5HLfaDHj2iHoSiHsuiHhKjHlmjHqCjHufDXi5EveoIe/ES9hoo7KXU11dkfwIMAGrqXlvZzuz6AAAAAElFTkSuQmCC"
                            alt="forumNav" />
                    </a>
                    <a class="circle <?php echo ($_SERVER['REQUEST_URI'] == '/Users/Info') ? 'active' : ''; ?>"
                        href="/Users/Info">
                        <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEYAAABGCAMAAABG8BK2AAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAABhQTFRFHx4e//5ytwAAz8/PAAAA/////wAA////+AI2SgAAAAh0Uk5T/////////wDeg71ZAAACUUlEQVR42qzYi5KFIAgAULTS///j9YGIAnabxZlt61pn6AFakE8NWDvveCQu1o4UmMalNFMCHRnHPdSm9CMzkEc0GwKJRJVgVJTQ/gPEGFN6Di2lsgscmRJKQdLJad1xDwhkKEcHu/eAYFUStaPSA9KZRXllVgcMpTv80duU1QFLSSvSoWQ5YCjlGspciGInEMyGIDPyezArtDNrMLEzPKcbFXeHwgFTAS1PLEdhUFETeXcWRipmXQHdEYwdCg9IY74ohrMx78ruEFOeibMSSrOd8mwhw+qIwoSQksW0xOMMlrVNqXFUJainhWW1MTArb+lrJxBmS4G32VN2ndUZGjPLdWHqCdRFDSHVRWjR00bfLg47bGVq8m1MoEXqTsLtwIYPyVx0KDI3uT0KZMLOsEuDTD8JZDJZTegd9R9nai24nlM0mUUzzq118DFRMLBfmzwv0Oq/M/e8FCHTnQq1Y9ypV2Z9bjAV8Md8z54zk++7HFoW9f9Y74ulA35kyl9b7Svtx9wXbfuNkY1Ob0mr70y7pP9nZIq/MeTMO5Vnbmc6ObgEc6kMPi24So+hxrTpIFgMngyrFxYjC8XC7MUnTSbaTC1bw5lJQQy7Y7WKirLFiyhnAkXRAwkLsxVRvaSL6jcS3Crp+gBDd2pen57ghwFGGe7wubnbCiZ2S3BzuNMHX5HhtG4MvvpUYOT1wtSFORVwmph4TZOcJm1eU0inCa3X9Nprsu/06uH1IuT1Wub1kuj1yur1Au31Ou/2ccHrU4fbhxe3z0BuH6W+fiL7E2AA/wJc7ppsF78AAAAASUVORK5CYII="
                            alt="infoNav" />
                    </a>
                </div>
                <div class="flex flex-row justify-center items-center my-5 gap-2 flex-wrap">
                    <?php if ($_Login == null) { ?>
                        <a class="bg-gray-800 px-4 py-1.5 rounded text-white text-sm hover:underline hover:bg-gray-600 max-sm:w-32 max-sm:my-1 text-center <?php echo ($_SERVER['REQUEST_URI'] == '/Auth/Register') ? 'active' : ''; ?>"
                            href="/Auth/Register">Đăng Ký</a>
                        <a class="bg-gray-800 px-4 py-1.5 rounded text-white text-sm hover:underline hover:bg-gray-600 max-sm:w-32 max-sm:my-1 text-center <?php echo ($_SERVER['REQUEST_URI'] == '/Auth/Login') ? 'active' : ''; ?>"
                            href="/Auth/Login">Đăng Nhập</a>
                    <?php } else { ?>
                        <a class="bg-gray-800 px-4 py-1.5 rounded text-white text-sm hover:underline hover:bg-gray-600 max-sm:w-32 max-sm:my-1 text-center <?php echo ($_SERVER['REQUEST_URI'] == '/Users/Payment') ? 'active' : ''; ?>"
                            href="/Users/Payment">Nạp Tiền</a>
                        <a class="bg-gray-800 px-4 py-1.5 rounded text-white text-sm hover:underline hover:bg-gray-600 max-sm:w-32 max-sm:my-1 text-center <?php echo ($_SERVER['REQUEST_URI'] == '/Users/Profile') ? 'active' : ''; ?>"
                            href="/Users/Profile">Tài Khoản</a>
                        <?php if ($_Admin == 1) { ?>
                            <a class="bg-gray-800 px-4 py-1.5 rounded text-red-500 font-bold text-sm hover:underline hover:bg-gray-600 max-sm:w-32 max-sm:my-1 text-center"
                                href="/Admin/index.php">Admin Panel</a>
                        <?php } ?>
                        <a class="bg-gray-800 px-4 py-1.5 rounded text-white text-sm hover:underline hover:bg-gray-600 max-sm:w-32 max-sm:my-1 text-center"
                            href="/Auth/Logout">Đăng Xuất</a>
                    <?php } ?>
                    <a class="bg-gray-800 px-4 py-1.5 rounded text-white text-sm hover:underline hover:bg-gray-600 max-sm:w-32 max-sm:my-1 text-center <?php echo (strpos($_SERVER['REQUEST_URI'], '/Users/Rankings') === 0) ? 'active' : ''; ?>"
                        href="/Users/Rankings">Bảng Xếp Hạng</a>
                    <a rel="noopener noreferrer"
                        class="bg-gray-800 px-4 py-1.5 rounded text-white text-sm hover:underline hover:bg-gray-600 max-sm:w-32 max-sm:my-1 text-center"
                        href="<?= $_Fanpage ?>" target="b_blank">Fanpage</a>
                </div>
