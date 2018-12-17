本文借鉴于https://github.com/liyangqit/refund/tree/master,进行改良
1.将文件放在libraries(CI)或者vendor下  

2.初始化文件 $wxRefund = new wxRefund($mchid,$appid,$appKey,$apiKey);  

3.调用 $wxRefund->createRefund($params);//其中$params为你自己的订单参数
