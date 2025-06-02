CREATE TABLE [dbo].[import_tasks](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[status] [varchar](20) NOT NULL,
	[target_data_name] [varchar](100) NOT NULL,
	[original_file_name] [nvarchar](255) NOT NULL,
	[stored_file_path] [nvarchar](512) NOT NULL,
	[uploaded_at] [datetime2](7) NOT NULL,
	[uploaded_by] [nvarchar](100) NULL,
	[processing_started_at] [datetime2](7) NULL,
	[processing_finished_at] [datetime2](7) NULL,
	[result_message] [nvarchar](max) NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = OFF) ON [PRIMARY]
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]
GO

ALTER TABLE [dbo].[import_tasks] ADD  DEFAULT ('pending') FOR [status]
GO

ALTER TABLE [dbo].[import_tasks] ADD  DEFAULT (getdate()) FOR [uploaded_at]
GO

