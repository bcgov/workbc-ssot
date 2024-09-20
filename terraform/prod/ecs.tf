# ecs.tf

resource "aws_ecs_cluster" "main" {
  name               = "workbc-ssot-cluster"
  capacity_providers = ["FARGATE_SPOT"]

  default_capacity_provider_strategy {
    capacity_provider = "FARGATE_SPOT"
    weight            = 100
  }

  tags = var.common_tags
}

resource "aws_ecs_task_definition" "app" {
#  count                    = local.create_ecs_service
  count                    = 1
  family                   = "workbc-ssot-task"
  execution_role_arn       = aws_iam_role.ecs_task_execution_role.arn
  task_role_arn            = aws_iam_role.workbc_ssot_container_role.arn
  network_mode             = "awsvpc"
  cpu                      = var.fargate_cpu
  memory                   = var.fargate_memory
  tags                     = var.common_tags


  container_definitions = jsonencode([
	{
		essential   = true
		name        = "postgrest"
		image       = "${var.app_repo}/postgrest:1.0"
		networkMode = "awsvpc"
		
		logConfiguration = {
			logDriver = "awslogs"
			options = {
				awslogs-create-group  = "true"
				awslogs-group         = "/ecs/${var.app_name}"
				awslogs-region        = var.aws_region
				awslogs-stream-prefix = "ecs"
			}
		}		

		portMappings = [
			{
				hostPort = 3000
				protocol = "tcp"
				containerPort = 3000
			}
		]
		
		environment = [
			{
				name = "PGRST_DB_URI",
				value = "${local.conn_str}"
			},
			{
				name = "PGRST_DB_SCHEMA",
				value = "public"
			},
			{
				name = "PGRST_DB_ANON_ROLE",
				value = "ssot_lmmu"
			},
			{
				name = "PGRST_OPENAPI_SERVER_PROXY_URI",
				value = "http://localhost:3000"
			}
		]



	},
	{
		essential   = false
		name        = "swagger"
		image       = "${var.app_repo}/swagger:1.0"
		networkMode = "awsvpc"

		portMappings = [
			{
				hostPort = 8080
				protocol = "tcp"
				containerPort = 8080
			}
		]
		environment = [
			{
				name = "API_URL",
				value = "http://localhost:3000/"
			}
		]

		dependsOn = [
			{
				containerName = "postgrest"
				condition = "START"
			}
		]
	}
  ])
}

resource "aws_ecs_service" "main" {
#  count                             = local.create_ecs_service
  count                             = 1
  name                              = "workbc-ssot-service"
  cluster                           = aws_ecs_cluster.main.id
  task_definition                   = aws_ecs_task_definition.app[count.index].arn
  desired_count                     = var.app_count
  enable_ecs_managed_tags           = true
  propagate_tags                    = "TASK_DEFINITION"
#  health_check_grace_period_seconds = 60
  wait_for_steady_state             = false
  enable_execute_command            = true


  capacity_provider_strategy {
    capacity_provider = "FARGATE_SPOT"
    weight            = 100
  }


  network_configuration {
    security_groups  = [data.aws_security_group.app.id]
    subnets          = module.network.aws_subnet_ids.app.ids
    assign_public_ip = false
  }

  load_balancer {
    target_group_arn = aws_alb_target_group.app.id
    container_name   = "postgrest"
    container_port   = var.app_port
  }

  depends_on = [aws_alb_listener.front_end, aws_iam_role_policy_attachment.ecs_task_execution_role]

  tags = var.common_tags
}
