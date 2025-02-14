<?php

namespace App\Http\Controllers;

use App\Models\BoardMembers;
use App\Models\Task;
use App\Models\TaskAssignedTo;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TaskAssignedToController extends ApiController
{
    public function assignTaskToUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "task_id" => "required|exists:tasks,id",
                "board_id" => "required",
                "email" => "required|exists:users,email"
            ]);
            if ($validator->fails()) {
                return $this->sendError($validator->messages()->toArray());
            }
            $user = User::where("email", $request->get("email"))->first();
            $userID = $user->id;
            $authUser = Auth::user();
            $isAuthUserBoardMember = BoardMembers::where("user_id", $authUser->id)->where("board_id", $request->get("board_id"))->first();
            if (!$isAuthUserBoardMember) {
                return $this->sendError("Not allowed to perform this action", [], Response::HTTP_METHOD_NOT_ALLOWED);
            }
            $isUserBoardMember = BoardMembers::where("user_id", $userID)->where("board_id", $request->get("board_id"))->first();
            if (!$isUserBoardMember) {
                return $this->sendError("This user is not a board member", []);
            }
            $isUserAlreadyAsigned = TaskAssignedTo::where("task_id", $request->get("task_id"))->where("assigned_to", $userID)->first();
            if ($isUserAlreadyAsigned) {
                return $this->sendError("This user is already assigned", []);
            }

            $assignUser = new TaskAssignedTo();
            $assignUser->task_id = $request->get("task_id");
            $assignUser->assigned_to = $userID;
            $assignUser->save();

            return $this->sendResponse([], 201);
        } catch (Exception $exception) {
            error_log($exception);
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getAssignedUsers($id)
    {
        try {
            $authUser = Auth::user();

            $task = Task::find($id);
            if (!$task) {
                return $this->sendError("Task not found", [], Response::HTTP_NOT_FOUND);
            }
            $status = $task->status;
            $taskBelongsToBoardID = $status->board_id;

            if (!$authUser->isSuperAdmin) {
                $isUserBoardMember = BoardMembers::where("user_id", $authUser->id)->where("board_id", $taskBelongsToBoardID)->first();
                if (!$isUserBoardMember) {
                    return $this->sendError("Not allowed to perform this action", [], Response::HTTP_METHOD_NOT_ALLOWED);
                }
            }
            $isCurrentUserAssigned = TaskAssignedTo::where("assigned_to", $authUser->id)->where("task_id", $id)->first();
            $assignedUsers = TaskAssignedTo::query()->where("task_id", $id)->paginate(30);
            $result = [
                "users" => [],
                "currentPage" => $assignedUsers->currentPage(),
                "hasMorePages" => $assignedUsers->hasMorePages(),
                "lastPage" => $assignedUsers->lastPage(),
                "isCurrentUserAssigned" => $isCurrentUserAssigned ? true : false
            ];

            foreach ($assignedUsers->items() as $assigned) {
                $assignedUser = $assigned->getUser;
                $result["users"][] = $assignedUser;
            }
            return $this->sendResponse($result);
        } catch (Exception $exception) {
            error_log($exception);
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
