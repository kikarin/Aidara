<?php

namespace App\Http\Controllers;

use App\Repositories\JenisPendidikanRepository;
use App\Repositories\KelurahanRepository;
use App\Repositories\PencakerRepository;
use App\Repositories\PerusahaanRepository;
use App\Repositories\UsersRepository;
use Illuminate\Contracts\Encryption\DecryptException as ErrorDecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class GeneralController extends Controller
{
    private $usersRepository;

    protected $kelurahanRepository;

    protected $jenisPendidikanRepository;

    protected $pencakerRepository;

    protected $perusahaanRepository;

    public function __construct(
        UsersRepository $usersRepository,
        KelurahanRepository $kelurahanRepository,
        JenisPendidikanRepository $jenisPendidikanRepository,
        PencakerRepository $pencakerRepository,
        PerusahaanRepository $perusahaanRepository
    ) {
        $this->usersRepository           = $usersRepository;
        $this->kelurahanRepository       = $kelurahanRepository;
        $this->jenisPendidikanRepository = $jenisPendidikanRepository;
        $this->pencakerRepository        = $pencakerRepository;
        $this->perusahaanRepository      = $perusahaanRepository;
    }

    public function access_file($direktori, $file_name)
    {
        try {
            $direktori = Crypt::decrypt($direktori);
            $file_name = Crypt::decrypt($file_name);
        } catch (ErrorDecryptException $e) {
            abort(404);
        }
        abort_if(! in_array($direktori, ['users']) || is_null($file_name), 404);
        $data = [
            'direktori' => $direktori,
            'file_name' => $file_name,
        ];
        if (in_array($direktori, ['users'])) {
            if (Auth::user()->current_role_id == 100) {
                $data['is_my_file'] = 1;
            }
            $getFile   = $this->usersRepository->getByFile($data);
            $file_path = @$getFile['file_path'];
        }
        abort_if(! $getFile, 404);

        return response()->file($file_path);
    }

    public function kelurahan(Request $request)
    {
        $data = $this->kelurahanRepository->getAll(['kecamatan_id' => $request->kecamatan_id]);

        return response()->json([
            'error' => 0,
            'data'  => $data,
        ]);
    }

    public function jenis_pendidikan(Request $request)
    {
        $data = $this->jenisPendidikanRepository->getAll(['kategori_pendidikan_id' => $request->kategori_pendidikan_id]);

        return response()->json([
            'error' => 0,
            'data'  => $data,
        ]);
    }

    public function detail_pencaker($pencaker_id)
    {
        $item = $this->pencakerRepository->getFind($pencaker_id);
        $data = [
            'item' => $item,
        ];
        $result = view('pencaker.detail-pencaker.detail-tab', $data)->render();

        return response()->json([
            'error' => 0,
            'html'  => $result,
        ]);
    }

    public function search_pencaker(Request $request)
    {
        $data = $this->pencakerRepository->search($request->all());

        return response()->json(['results' => $data]);
    }

    public function search_perusahaan(Request $request)
    {
        $data = $this->perusahaanRepository->search($request->all());

        return response()->json(['results' => $data]);
    }
}
