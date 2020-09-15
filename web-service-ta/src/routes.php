<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;
use Sk\Geohash\Geohash;

date_default_timezone_set("Asia/Jakarta");

return function (App $app) {
    $container = $app->getContainer();
    $container['upload_directory'] = __DIR__ . '/uploads';

    $app->get('/getAllKategoriLostFound', function ($request, $response) {
        $sql = "SELECT * FROM setting_kategori_lostfound";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $response->withJson($result, 200);
    });

    $app->get('/getHeadlineLaporanLostFound', function ($request, $response) {
        $sql = "SELECT * FROM laporan_lostfound_barang order by tanggal_laporan desc, waktu_laporan asc LIMIT 5";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $response->withJson($result, 200);
    });

    $app->get('/getHeadlineLaporanKriminalitas', function ($request, $response) {
        $sql = "SELECT * FROM laporan_kriminalitas order by tanggal_laporan desc, waktu_laporan desc LIMIT 5";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $response->withJson($result, 200);
    });
    
    $app->get('/getAllKategoriCrime', function ($request, $response) {
        $sql = "SELECT * FROM setting_kategori_kriminalitas";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $response->withJson($result, 200);
    });
    

    $app->get('/getAllKantorPolisi', function ($request, $response) {   
        $sql = "SELECT * FROM kantor_polisi";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        return $response->withJson($result, 200);
    });

    $app->group('/user', function () use ($app) {
        $app->get('/getAllUser', function ($request, $response) {
            $sql = "SELECT * FROM user";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll();
            return $response->withJson(["status" => "success", "users" => $result], 200);
        });

        $app->post('/sendOTP', function($request,$response){
            $body = $request->getParsedBody();
            $url = "https://numberic1.tcastsms.net:20005/sendsms?account=def_robby3&password=123456";
            $code=rand(1000,9999);
            $message="Masukkan nomor ".$code.". Mohon tidak menginformasikan nomor ini kepada siapa pun";
            $api_url=$url."&numbers=".$body["number"]."&content=".rawurlencode($message);
            // $ch= curl_init();
            // curl_setopt($ch, CURLOPT_URL, $api_url);
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            //     'Content-Type:application/json',
            //     'Accept:application/json'
            // ));
            // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            // curl_setopt($ch, CURLOPT_HEADER        , FALSE);
    
            // $res = curl_exec($ch);
            // $httpCode= curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // curl_close($ch);

            $new_date = (new DateTime())->modify('+5 minutes');
            $expiredToken = $new_date->format('Y/m/d H:i:s'); 
            $sql = "UPDATE user set otp_code=:otp_code,otp_code_available_until=:otp_code_available_until where telpon_user=:telpon_user";
            $stmt = $this->db->prepare($sql);
            $data = [
                ":otp_code_available_until"=>$expiredToken,
                ":otp_code" => $code,
                ":telpon_user"=>$body["number"]
            ];
            //return $response->withJson($api_url);
            if($stmt->execute($data)){
                return $response->withJson(["status_code" => "200"]);
            }else{
                return $response->withJson(["status_code" => "400"]);
            }
        });
        
        $app->post('/verifyOTP', function($request,$response){
            $body = $request->getParsedBody();
            $date = new DateTime();
            $sql="SELECT otp_code,otp_code_available_until from user where telpon_user='".$body["number"]."'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll();
            if($result[0]["otp_code"]==$body["otp_code"]){
                if($date<new DateTime($result[0]["otp_code_available_until"])){
                    $sql="UPDATE user set status_user=0,otp_code=null,otp_code_available_until=null where telpon_user='".$body["number"]."'";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute();
                    return $response->withJson(["status"=>"1","message"=>"Verify kode OTP berhasil"]);
                }else{
                    return $response->withJson(["status"=>"2","message"=>"Kode OTP telah Kadaluarsa, silahkan request kode OTP yang baru"]);
                }
            }else{
                return $response->withJson(["status"=>"99","message"=>"Kode OTP yang anda masukkan tidak sesuai"]);
            }
        });

        $app->post('/checkLogin', function ($request, $response) {
            $user = $request->getParsedBody();
            $email=$user["email"];
            $password=$user["password"];
            $sql = "SELECT * FROM user where email_user='$email' and password_user='$password'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            if($result==null){
                return $response->withJson(["status" => "400"]);
            }else{
                return $response->withJson(["status" => "200", "data" => $result], 200);
            }
        });

        $app->get('/getUser/{id}', function ($request, $response,$args) {
            $id=$args["id"];
            $sql = "SELECT * FROM user where id_user=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([":id" => $id]);
            $result = $stmt->fetch();
            return $response->withJson($result);
        });

        $app->put('/updateCreditCardToken', function ($request, $response) {
            $body = $request->getParsedBody();
            $sql = "UPDATE user set credit_card_token=:credit_card_token where id_user=:id_user";
            $stmt = $this->db->prepare($sql);
            $data = [
                ":credit_card_token" => $body["credit_card_token"],
                ":id_user"=>$body["id_user"]
            ];
            if($stmt->execute($data)){
                return $response->withJson(["status" => "success", "data" => "1"], 200);
            }else{
                return $response->withJson(["status" => "failed", "data" => "0"], 200);
            }
        });

        $app->post('/chargeUser', function ($request, $response) {
            $body = $request->getParsedBody();
            $id_user=$body["id_user"];
            $sql = "SELECT * FROM user where id_user='$id_user'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $row = $stmt->fetch();
            $credit_card_token = $row["credit_card_token"];    
            $milliseconds = round(microtime(true) * 1000);
            $id_order="ORDER".$milliseconds;
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.sandbox.midtrans.com/v2/charge",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS =>  json_encode([
                    "payment_type" => "credit_card",
                    "transaction_details" => [
                        "order_id" => $id_order,
                        "gross_amount" => 50000
                    ],
                    "credit_card" => [
                        "token_id" => $credit_card_token,
                    ],
                    "item_details" => [[
                        "id" => "SSMEMBER01",
                        "price" => 50000,
                        "quantity" => 1,
                        "name" => "Membership Sahabat Surabaya 1 Bulan",
                        "merchant_name" => "Sahabat Surabaya"
                    ]],
                    "customer_details" => [
                        "first_name" => $row["nama_user"],
                        "email" => $row["email_user"],
                        "phone" => $row["telpon_user"],
                    ]
                ]),
                CURLOPT_HTTPHEADER => array(
                    "Content-Type: application/json",
                    "Accept: application/json",
                    "Authorization: Basic U0ItTWlkLXNlcnZlci1GQjRNSERieVhlcFc5OFNRWjY0SHhNeEU="
                ),
            ));
            $curl_response = curl_exec($curl);  
            $json = json_decode(utf8_encode($curl_response), true);
            curl_close($curl);
            if($json["status_code"]=="200"){
                 $datetime=date('Y-m-d');
                $sql = "INSERT INTO order_subscription(id_order,order_ammount, order_date) VALUE (:id_order,:order_ammount,:order_date)";
                $data = [
                    ":id_order" => $id_order,
                    ":order_ammount"=>50000,
                    ":order_date" => $datetime
                ];
                $stmt=$this->db->prepare($sql);
                $stmt->execute($data);
                $available_until = strtotime($datetime);
                $final = date("Y-m-d", strtotime("+1 month", $available_until));
                $sql="UPDATE user set premium_available_until=:premium_available_until,status_user=1 where id_user=:id_user";
                $data=[
                  ":premium_available_until"=> $final,
                  ":id_user"=>$id_user
                ];
                $stmt=$this->db->prepare($sql);
                $stmt->execute($data);
                return $response->withJson(200); 
            }else{
                return $response->withJson(400); 
            }
        });

       $app->post('/registerUser', function ($request, $response) {
            $new_user = $request->getParsedBody();
            $sql="SELECT COUNT(*) from user where email_user='".$new_user["email_user"]."'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $email_kembar = $stmt->fetchColumn();
            $sql="SELECT COUNT(*) from user where telpon_user='".$new_user["telpon_user"]."'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $telpon_kembar = $stmt->fetchColumn();
            if($email_kembar==1 || $telpon_kembar==1){
                if($email_kembar==1){
                    return $response->withJson(["status"=>"2","message"=>"Email yang dimasukkan telah terpakai"]);
                }else if($telpon_kembar==1){
                    return $response->withJson(["status"=>"3","message"=>"No. Handphone yang dimasukkan telah terpakai"]);
                }
            }else{
                $geohash=new Geohash();
                $lat_user="";
                $lng_user="";
                $alamat_user=""; 
                $geohashAlamat=null;          
                if($new_user["lat_user"]=="default"){
                    $lat_user=null;
                }else{
                    $lat_user=$new_user["lat_user"];
                }
                if($new_user["lng_user"]=="default"){
                    $lng_user=null;
                }else{
                    $lng_user=$new_user["lng_user"];
                }
                if($new_user["alamat_user"]=="default"){
                    $alamat_user=null;
                }else{
                    $alamat_user=$new_user["alamat_user"];
                }
                if($lng_user!=null && $lat_user!=null){
                    $geohashAlamat=$geohash->encode(floatval($lat_user), floatval($lng_user), 8);
                }
                $sql = "INSERT INTO user (email_user,password_user, nama_user, telpon_user, status_user, lat_user,lng_user,alamat_user,geohash_alamat_user) VALUE (:email_user,:password_user, :nama_user, :telpon_user, :status_user, :lat_user,:lng_user,:alamat_user,:geohash_alamat_user)";
                $stmt = $this->db->prepare($sql);
                $data = [
                    ":email_user" => $new_user["email_user"],
                    ":password_user"=>$new_user["password_user"],
                    ":nama_user" => $new_user["nama_user"],
                    ":telpon_user" => $new_user["telpon_user"],
                    ":status_user"=>99,
                    ":lat_user"=>$lat_user,
                    ":lng_user"=>$lng_user,
                    ":alamat_user"=>$alamat_user,
                    ":geohash_alamat_user"=>$geohashAlamat
                ];
                if($stmt->execute($data)){
                    return $response->withJson(["status" => "1","message"=>"Register akun berhasil!","insertID"=>$this->db->lastInsertId()]);
                }else{
                    return $response->withJson(["status" => "99","message"=>"Register gagal, silahkan coba beberapa saat lagi"]);
                }
            }
        });

        $app->get('/getEmergencyContact/{id_user}', function($request, $response, $args){
            $id_user=$args["id_user"];
            $sql="SELECT * from user where id_user in (SELECT case when id_user_1=:id_user then id_user_2 else id_user_1 end from daftar_kontak_darurat where status_relasi=1 and (id_user_1=:id_user or id_user_2=:id_user));";
            $data=[
                ":id_user"=>$id_user
            ];
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            $result = $stmt->fetchAll();
            return $response->withJson($result);
        }); 

        $app->get('/getSentPendingContactRequest/{id_user}', function($request, $response, $args){
            $id_user=$args["id_user"];
            $sql="SELECT * from user where id_user in (SELECT id_user_2 FROM daftar_kontak_darurat where id_user_1=".$id_user." and status_relasi=0)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll();
            return $response->withJson($result);
        }); 
        
        $app->get('/getPendingContactRequest/{id_user}', function($request, $response, $args){
            $id_user=$args["id_user"];
            $sql="SELECT * from user where id_user in (SELECT id_user_1 FROM daftar_kontak_darurat where id_user_2=".$id_user." and status_relasi=0)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll();
            return $response->withJson($result);
        }); 
        
        $app->post('/addEmergencyContact', function($request, $response){
            $body = $request->getParsedBody();
            $id_user_pengirim=$body["id_user_pengirim"];
            $sql  = "SELECT * from user where telpon_user='".$body["nomor_tujuan"]."'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $user_found = $stmt->fetchAll();
            if($user_found!=null){
                $sql="SELECT * from daftar_kontak_darurat where (id_user_1=:id_user_1 and id_user_2=:id_user_2) OR (id_user_1=:id_user_2 and id_user_2=:id_user_1)";
                $stmt = $this->db->prepare($sql);
                $data = [
                    ":id_user_1" => $id_user_pengirim,
                    ":id_user_2"=>$user_found[0]["id_user"]
                ];
                $stmt->execute($data);
                $duplicateRecord = $stmt->fetchAll();
                if($duplicateRecord==null){
                    $sql="INSERT INTO daftar_kontak_darurat (id_user_1,id_user_2,status_relasi) VALUE (:id_user_1,:id_user_2,:status_relasi)";
                    $data = [
                        ":id_user_1" => $id_user_pengirim,
                        ":id_user_2"=>$user_found[0]["id_user"],
                        ":status_relasi"=>0
                    ];
                    $stmt = $this->db->prepare($sql);
                    if($stmt->execute($data)){
                        return $response->withJson(["status"=>"1","message"=>"Request kontak telah dikirimkan ke nomor ".$body["nomor_tujuan"]]);
                    }else{
                        return $response->withJson(["status"=>"400","message"=>"Tambah kontak gagal, silahkan coba beberapa saat lagi"]);
                    }
                }else{
                    $message="";
                    if($duplicateRecord[0]["status_relasi"]==0 && $duplicateRecord[0]["id_user_1"]==$id_user_pengirim){
                        $message="Anda sudah mengirimkan request untuk menambahkan user tersebut sebelumnya, silahkan tunggu user tersebut untuk menerima.";
                    }else if($duplicateRecord[0]["status_relasi"]==0 && $duplicateRecord[0]["id_user_2"]==$id_user_pengirim){
                        $message="User tersebut telah mengirimkan request untuk menambahkan anda kedalam kontaknya.";
                    }else{
                        $message="User dengan nomor tersebut telah terdaftar pada kontak anda.";
                    }
                    return $response->withJson(["status"=>"99","message"=>$message]);
                }       
            }else{
                return $response->withJson(["status"=>"99","message"=>"User dengan nomor tersebut tidak ditemukan"]);
            }
        });
    });
        
        $app->post('/insertKomentarLaporanLostFound', function ($request, $response) {
            $new_komentar = $request->getParsedBody();
            $datetime = DateTime::createFromFormat('d/m/Y', $new_komentar["tanggal_komentar"]);
            $day=$datetime->format('d');
            $month=$datetime->format('m');
            $year=$datetime->format('Y');
            $formatDate=$year.$month.$day;
            $sql = "INSERT INTO komentar_laporan_lostfound (id_laporan,isi_komentar, tanggal_komentar, waktu_komentar,email_user) VALUE (:id_laporan,:isi_komentar, :tanggal_komentar, :waktu_komentar, :email_user)";
            $stmt = $this->db->prepare($sql);
            $data = [
                ":id_laporan" => $new_komentar["id_laporan"],
                ":isi_komentar"=>$new_komentar["isi_komentar"],
                ":tanggal_komentar" => $formatDate,
                ":waktu_komentar" => $new_komentar["waktu_komentar"],
                ":email_user" => $new_komentar["email_user"]
            ];
        
            if($stmt->execute($data))
            return $response->withJson(["status" => "success", "data" => "1"], 200);
            
            return $response->withJson(["status" => "failed", "data" => "0"], 200);
            });

            $app->get('/getHeaderChat/{id_user}', function ($request, $response,$args) {   
                $id_user=$args["id_user"];
                $sql = "SELECT h.id_chat,h.id_user_1,h.id_user_2,u.nama_user as nama_user_1,u2.nama_user as nama_user_2 from header_chat h,user u, user u2 where h.id_user_1=u.id_user and h.id_user_2=u2.id_user and (h.id_user_1=:id_user OR h.id_user_2=:id_user)";
                $stmt = $this->db->prepare($sql);
                $data = [
                    ":id_user" => $id_user
                ];
                $stmt->execute($data);
                $result = $stmt->fetchAll();
                return $response->withJson($result, 200);
            });

            $app->get('/getAllChat/{id_chat}', function ($request, $response,$args) {   
                $id_chat=$args["id_chat"];
                $sql = "SELECT * from detail_chat where id_chat=:id_chat";
                $stmt = $this->db->prepare($sql);
                $data = [
                    ":id_chat" => $id_chat
                ];
                $stmt->execute($data);
                $result = $stmt->fetchAll();
                return $response->withJson($result);
            });

            $app->get('/getLastMessage/{id_chat}', function ($request, $response,$args) {   
                $id_chat=$args["id_chat"];
                $sql = "SELECT d.id_chat,d.id_user_pengirim,d.id_user_penerima,d.isi_chat,d.waktu_chat,u1.nama_user as nama_user_pengirim,u2.nama_user as nama_user_penerima FROM detail_chat d,user u1,user u2 where d.id_chat=:id_chat and d.id_user_pengirim=u1.id_user and d.id_user_penerima=u2.id_user order by d.waktu_chat desc LIMIT 1";
                $stmt = $this->db->prepare($sql);
                $data = [
                    ":id_chat" => $id_chat
                ];
                $stmt->execute($data);
                $result = $stmt->fetch();
                return $response->withJson($result);
            });

            $app->get('/checkHeaderChat/{id_user_1}/{id_user_2}', function ($request, $response,$args) {   
                $id_user_1=$args["id_user_1"];
                $id_user_2=$args["id_user_2"];
                $sql = "SELECT id_chat from header_chat where id_user_1=:id_user_1 and id_user_2=:id_user_2 or id_user_1=:id_user_2 and id_user_2=:id_user_1";
                $stmt = $this->db->prepare($sql);
                $data = [
                    ":id_user_1" => $id_user_1,
                    ":id_user_2" => $id_user_2
                ];
                $stmt->execute($data);
                $result = $stmt->fetchColumn();
                if($result==false){
                    $sql = "INSERT INTO header_chat (id_user_1,id_user_2)VALUE(:id_user_1,:id_user_2)";
                    $stmt = $this->db->prepare($sql);
                    $data = [
                        ":id_user_1" => $id_user_1,
                        ":id_user_2"=>$id_user_2
                    ];
                    if($stmt->execute($data)){
                        return $response->withJson($this->db->lastInsertId());
                    }else{
                        return $response->withJson(400);
                    }
                }else{
                    return $response->withJson($result);
                }
                
            });

            $app->post('/insertHeaderChat', function ($request, $response) {
                $body = $request->getParsedBody();
                $sql = "INSERT INTO header_chat (id_user_1,id_user_2)VALUE(:id_user_1,:id_user_2)";
                $stmt = $this->db->prepare($sql);
                $data = [
                    ":id_user_1" => $body["id_user_1"],
                    ":id_user_2"=>$body["id_user_2"]
                ];
                if($stmt->execute($data)){
                    return $response->withJson(200);
                }else{
                    return $response->withJson(400);
                }
            });
            

            $app->post('/insertDetailChat', function ($request, $response) {
                $new_chat = $request->getParsedBody();
                $datetime = date("Y/m/d H:i");
                $sql = "INSERT INTO detail_chat (id_chat,id_user_pengirim, id_user_penerima, isi_chat, waktu_chat) VALUE (:id_chat,:id_user_pengirim,:id_user_penerima, :isi_chat, :waktu_chat)";
                $stmt = $this->db->prepare($sql);
                $data = [
                    ":id_chat"=>$new_chat["id_chat"],
                    ":id_user_pengirim" => $new_chat["id_user_pengirim"],
                    ":id_user_penerima"=>$new_chat["id_user_penerima"],
                    ":isi_chat" => $new_chat["isi_chat"],
                    ":waktu_chat" => $datetime
                ];
            
                if($stmt->execute($data))
                return $response->withJson(["status" => "success", "data" => "1"], 200);
                
                return $response->withJson(["status" => "failed", "data" => "0"], 200);
                });

        $app->post('/insertLaporanLostFound', function(Request $request, Response $response,$args) {
            $new_laporan = $request->getParsedBody();
            $datetime = DateTime::createFromFormat('d/m/Y', $new_laporan["tanggal_laporan"]);
            $day=$datetime->format('d');
            $month=$datetime->format('m');
            $year=$datetime->format('Y');
            $formatDate=$year.$month.$day;
            $id_laporan="LF".$day.$month.$year;
            $geohash=new Geohash();
            $sql="SELECT COUNT(*)+1 from laporan_lostfound_barang where id_laporan like'%$id_laporan%'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchColumn();           
            $id_laporan=$id_laporan.str_pad($result,5,"0",STR_PAD_LEFT);
            $sql = "INSERT INTO laporan_lostfound_barang VALUES(:id_laporan,:judul_laporan,:jenis_laporan,:tanggal_laporan,:waktu_laporan,:alamat_laporan,:lat_laporan,:lng_laporan,:deskripsi_barang,:email_pelapor,:status_laporan,:geohash_alamat_laporan) ";
            $stmt = $this->db->prepare($sql);
            $data = [
                ":id_laporan" => $id_laporan,
                ":judul_laporan"=>$new_laporan["judul_laporan"],
                ":jenis_laporan" => $new_laporan["jenis_laporan"],
                ":alamat_laporan"=>$new_laporan["alamat_laporan"],
                ":tanggal_laporan"=>$formatDate,
                ":waktu_laporan"=>$new_laporan["waktu_laporan"],
                ":lat_laporan"=>$new_laporan["lat_laporan"],
                ":lng_laporan"=>$new_laporan["lng_laporan"],
                ":deskripsi_barang"=>$new_laporan["deskripsi_barang"],
                ":email_pelapor"=>$new_laporan["email_pelapor"],
                ":status_laporan"=>0,
                ":geohash_alamat_laporan"=> $geohash->encode(floatval($new_laporan["lat_laporan"]), floatval($new_laporan["lng_laporan"]), 8)
            ];
            $stmt->execute($data);
            $increment=1;
            $uploadedFiles = $request->getUploadedFiles();
            foreach($uploadedFiles['image'] as $uploadedFile){
                if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                    $id_gambar=$id_laporan.$increment;
                    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                    $filename=$id_gambar.".".$extension;
                    $sql = "INSERT INTO gambar_lostfound_barang VALUES(:id_gambar,:nama_file,:id_laporan) ";
                    $stmt = $this->db->prepare($sql);
                    $data = [
                        ":id_gambar" => $id_gambar,
                        ":nama_file"=>$filename,
                        ":id_laporan" => $id_laporan
                    ];
                    $stmt->execute($data);
                    $directory = $this->get('settings')['upload_directory'];
                    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename); 
                    $increment=$increment+1;
                }
            }
        });

        $app->post('/insertLaporanKriminalitas', function(Request $request, Response $response,$args) {
            $new_laporan = $request->getParsedBody();
            $datetime = DateTime::createFromFormat('d/m/Y', $new_laporan["tanggal_laporan"]);
            $day=$datetime->format('d');
            $month=$datetime->format('m');
            $year=$datetime->format('Y');
            $formatDate=$year.$month.$day;
            $id_laporan="CR".$day.$month.$year;
            $geohash=new Geohash();
            $sql="SELECT COUNT(*)+1 from laporan_kriminalitas where id_laporan like'%$id_laporan%'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchColumn();           
            $id_laporan=$id_laporan.str_pad($result,5,"0",STR_PAD_LEFT);
            $sql = "INSERT INTO laporan_kriminalitas VALUES(:id_laporan,:judul_laporan,:jenis_kejadian,:deskripsi_kejadian,:tanggal_laporan,:waktu_laporan,:alamat_laporan,:lat_laporan,:lng_laporan,:id_user_pelapor,:status_laporan,:geohash_alamat_laporan) ";
            $stmt = $this->db->prepare($sql);
            $data = [
                ":id_laporan" => $id_laporan,
                ":judul_laporan"=>$new_laporan["judul_laporan"],
                ":jenis_kejadian" => $new_laporan["jenis_kejadian"],
                ":deskripsi_kejadian"=>$new_laporan["deskripsi_kejadian"],
                ":tanggal_laporan"=>$formatDate,
                ":waktu_laporan"=>$new_laporan["waktu_laporan"],
                ":alamat_laporan"=>$new_laporan["alamat_laporan"],
                ":lat_laporan"=>$new_laporan["lat_laporan"],
                ":lng_laporan"=>$new_laporan["lng_laporan"],
                ":id_user_pelapor"=>$new_laporan["id_user_pelapor"],
                ":status_laporan"=>0,
                ":geohash_alamat_laporan"=> $geohash->encode(floatval($new_laporan["lat_laporan"]), floatval($new_laporan["lng_laporan"]), 8)
            ];
            $stmt->execute($data);
            $increment=1;
            $uploadedFiles = $request->getUploadedFiles();
            foreach($uploadedFiles['image'] as $uploadedFile){
                if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                    $id_gambar=$id_laporan.$increment;
                    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
                    $filename=$id_gambar.".".$extension;
                    $sql = "INSERT INTO gambar_kriminalitas VALUES(:id_gambar,:nama_file,:id_laporan) ";
                    $stmt = $this->db->prepare($sql);
                    $data = [
                        ":id_gambar" => $id_gambar,
                        ":nama_file"=>$filename,
                        ":id_laporan" => $id_laporan
                    ];
                    $stmt->execute($data);
                    $directory = $this->get('settings')['upload_directory'];
                    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename); 
                    $increment=$increment+1;
                }
            }
        });

        $app->get('/getKomentarLaporanLostFound/{id_laporan}', function ($request, $response,$args) {
            $id_laporan=$args["id_laporan"];
            $sql = "SELECT * FROM komentar_laporan_lostfound where id_laporan=:id_laporan order by tanggal_komentar DESC, waktu_komentar DESC ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([":id_laporan" => $id_laporan]);
            $result = $stmt->fetchAll();
            return $response->withJson($result, 200);
        });

        

        $app->get('/[{name}]', function (Request     $request, Response $response, array $args) use ($container) {
            // Sample log message
            $container->get('logger')->info("Slim-Skeleton '/' route");

            // Render index view
            return $container->get('renderer')->render($response, 'index.phtml', $args);
        });
};
