<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{
    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * @var Request
     */
    private $request;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository, Request $request)
    {
        $this->repository = $bookingRepository;
        $this->request = $request;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        if ($request->__authenticatedUser->user_type == env('ADMIN_ROLE_ID') || $request->__authenticatedUser->user_type == env('SUPERADMIN_ROLE_ID')) {
            return response($this->repository->getAll($request));
        }

        return response($this->repository->getUsersJobs($request->get('user_id')));
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        return response($this->repository->with('translatorJobRel.user')->find($id));
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $response = $this->repository->store($request->__authenticatedUser, $request->all());
        return response($response);

    }

    private function getRequestAndUser($key = '')
    {
        $data = !empty($key) ? $this->request->get($key) : $this->request->all();  
        return [$data, $this->request->__authenticatedUser];
    }

    private function getRequestAndResponse($method)
    {
        return $this->repository->$method($this->request->all());
    }

    /**
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        list($data, $user) = $this->getRequestAndUser();
        $response = $this->repository->updateJob($id, array_except($data, ['_token', 'submit']), $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $response = $this->repository->storeJobEmail($request->all());
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        $response = $this->repository->getUsersJobsHistory($request->get('user_id'), $request);
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob()
    {
        list($data, $user) = $this->getRequestAndUser();
        return response($this->repository->acceptJob($data, $user));
    }

    public function acceptJobWithId()
    {
        list($data, $user) = $this->getRequestAndUser('job_id');
        return response($this->repository->acceptJobWithId($data, $user));
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob()
    {
        list($data, $user) = $this->getRequestAndUser();
        return response($this->repository->cancelJobAjax($data, $user));
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob()
    {
        return response($this->getRequestAndResponse('endJob'));
    }

    public function customerNotCall(Request $request)
    {
        return response($this->getRequestAndResponse('customerNotCall'));

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        list($data, $user) = $this->getRequestAndUser();
        $response = $this->repository->getPotentialJobs($user);
        return response($response);
    }

    private function formatBoolean(&$data)
    {
        $data['flagged'] = filter_var($data['flagged'], FILTER_VALIDATE_BOOLEAN);
        $data['manually_handled'] = filter_var($data['manually_handled'], FILTER_VALIDATE_BOOLEAN);
        $data['by_admin'] = filter_var($data['by_admin'], FILTER_VALIDATE_BOOLEAN);
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();
        $distance = $data['distance'] ?? "";
        $time = $data['time'] ?? "";

        if (isset($data['jobid']) && $data['jobid'] != "") {
            $jobid = $data['jobid'];
        }

        $session = $data['session_time'] ?? "";
        $this->formatBoolean($data);

        if ($data['flagged'] && $data['admincomment'] == '') {
            return "Please, add comment";
        }

        $flagged = $data['flagged'] ? 'yes' : 'no';
        $manuallyHandled = $data['manually_handled'] ? 'yes' : 'no';
        $byAdmin = $data['by_admin'] ? 'yes' : 'no';
        $adminComment = $data['admincomment'] ?? "";

        if ($time || $distance) {
            $affectedRows = Distance::where('job_id', '=', $jobid)->update(array('distance' => $distance, 'time' => $time));
        }

        if ($adminComment || $session || $flagged || $manuallyHandled || $byAdmin) {
            $affectedRows1 = Job::where('id', '=', $jobid)->update(array('admin_comments' => $adminComment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manuallyHandled, 'by_admin' => $byAdmin));

        }

        return response('Record updated!');
    }

    public function reopen()
    {
        return response($this->getRequestAndResponse('reopen'));
    }

    public function resendNotifications(Request $request)
    {
        $job = $this->repository->find($request->get('jobid'));
        $jobData = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $jobData, '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        try {
            $job = $this->repository->find($request->get('jobid'));
            $this->repository->jobToData($job);
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }
}
